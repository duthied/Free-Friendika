<?php
/**
 * @file src/Protocol/ActivityPub/Receiver.php
 */
namespace Friendica\Protocol\ActivityPub;

use Friendica\Database\DBA;
use Friendica\Util\HTTPSignature;
use Friendica\Core\Protocol;
use Friendica\Model\Contact;
use Friendica\Model\APContact;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Util\JsonLD;
use Friendica\Util\LDSignature;
use Friendica\Protocol\ActivityPub;

/**
 * @brief ActivityPub Receiver Protocol class
 *
 * To-Do:
 * - Update (Image, Video, Article, Note)
 * - Event
 * - Undo Announce
 *
 * Check what this is meant to do:
 * - Add
 * - Block
 * - Flag
 * - Remove
 * - Undo Block
 * - Undo Accept (Problem: This could invert a contact accept or an event accept)
 *
 * General:
 * - Possibly using the LD-JSON parser
 */
class Receiver
{
	/**
	 * Checks if the web request is done for the AP protocol
	 *
	 * @return is it AP?
	 */
	public static function isRequest()
	{
		return stristr(defaults($_SERVER, 'HTTP_ACCEPT', ''), 'application/activity+json') ||
			stristr(defaults($_SERVER, 'HTTP_ACCEPT', ''), 'application/ld+json');
	}

	/**
	 * 
	 *
	 * @param $body
	 * @param $header
	 * @param integer $uid User ID
	 */
	public static function processInbox($body, $header, $uid)
	{
		$http_signer = HTTPSignature::getSigner($body, $header);
		if (empty($http_signer)) {
			logger('Invalid HTTP signature, message will be discarded.', LOGGER_DEBUG);
			return;
		} else {
			logger('HTTP signature is signed by ' . $http_signer, LOGGER_DEBUG);
		}

		$activity = json_decode($body, true);

		$actor = JsonLD::fetchElement($activity, 'actor', 'id');
		logger('Message for user ' . $uid . ' is from actor ' . $actor, LOGGER_DEBUG);

		if (empty($activity)) {
			logger('Invalid body.', LOGGER_DEBUG);
			return;
		}

		if (LDSignature::isSigned($activity)) {
			$ld_signer = LDSignature::getSigner($activity);
			if (empty($ld_signer)) {
				logger('Invalid JSON-LD signature from ' . $actor, LOGGER_DEBUG);
			}
			if (!empty($ld_signer && ($actor == $http_signer))) {
				logger('The HTTP and the JSON-LD signature belong to ' . $ld_signer, LOGGER_DEBUG);
				$trust_source = true;
			} elseif (!empty($ld_signer)) {
				logger('JSON-LD signature is signed by ' . $ld_signer, LOGGER_DEBUG);
				$trust_source = true;
			} elseif ($actor == $http_signer) {
				logger('Bad JSON-LD signature, but HTTP signer fits the actor.', LOGGER_DEBUG);
				$trust_source = true;
			} else {
				logger('Invalid JSON-LD signature and the HTTP signer is different.', LOGGER_DEBUG);
				$trust_source = false;
			}
		} elseif ($actor == $http_signer) {
			logger('Trusting post without JSON-LD signature, The actor fits the HTTP signer.', LOGGER_DEBUG);
			$trust_source = true;
		} else {
			logger('No JSON-LD signature, different actor.', LOGGER_DEBUG);
			$trust_source = false;
		}

		self::processActivity($activity, $body, $uid, $trust_source);
	}

	/**
	 * 
	 *
	 * @param array $activity
	 * @param integer $uid User ID
	 * @param $trust_source
	 *
	 * @return 
	 */
	private static function prepareObjectData($activity, $uid, &$trust_source)
	{
		$actor = JsonLD::fetchElement($activity, 'actor', 'id');
		if (empty($actor)) {
			logger('Empty actor', LOGGER_DEBUG);
			return [];
		}

		// Fetch all receivers from to, cc, bto and bcc
		$receivers = self::getReceivers($activity, $actor);

		// When it is a delivery to a personal inbox we add that user to the receivers
		if (!empty($uid)) {
			$owner = User::getOwnerDataById($uid);
			$additional = ['uid:' . $uid => $uid];
			$receivers = array_merge($receivers, $additional);
		}

		logger('Receivers: ' . json_encode($receivers), LOGGER_DEBUG);

		$object_id = JsonLD::fetchElement($activity, 'object', 'id');
		if (empty($object_id)) {
			logger('No object found', LOGGER_DEBUG);
			return [];
		}

		// Fetch the content only on activities where this matters
		if (in_array($activity['type'], ['Create', 'Announce'])) {
			$object_data = self::fetchObject($object_id, $activity['object'], $trust_source);
			if (empty($object_data)) {
				logger("Object data couldn't be processed", LOGGER_DEBUG);
				return [];
			}
			// We had been able to retrieve the object data - so we can trust the source
			$trust_source = true;
		} elseif (in_array($activity['type'], ['Like', 'Dislike'])) {
			// Create a mostly empty array out of the activity data (instead of the object).
			// This way we later don't have to check for the existence of ech individual array element.
			$object_data = self::processObject($activity);
			$object_data['name'] = $activity['type'];
			$object_data['author'] = $activity['actor'];
			$object_data['object'] = $object_id;
			$object_data['object_type'] = ''; // Since we don't fetch the object, we don't know the type
		} else {
			$object_data = [];
			$object_data['id'] = $activity['id'];
			$object_data['object'] = $activity['object'];
			$object_data['object_type'] = JsonLD::fetchElement($activity, 'object', 'type');
		}

		$object_data = self::addActivityFields($object_data, $activity);

		$object_data['type'] = $activity['type'];
		$object_data['owner'] = $actor;
		$object_data['receiver'] = array_merge(defaults($object_data, 'receiver', []), $receivers);

		logger('Processing ' . $object_data['type'] . ' ' . $object_data['object_type'] . ' ' . $object_data['id'], LOGGER_DEBUG);

		return $object_data;
	}

	/**
	 * 
	 *
	 * @param array $activity
	 * @param $body
	 * @param integer $uid User ID
	 * @param $trust_source
	 */
	public static function processActivity($activity, $body = '', $uid = null, $trust_source = false)
	{
		if (empty($activity['type'])) {
			logger('Empty type', LOGGER_DEBUG);
			return;
		}

		if (empty($activity['object'])) {
			logger('Empty object', LOGGER_DEBUG);
			return;
		}

		if (empty($activity['actor'])) {
			logger('Empty actor', LOGGER_DEBUG);
			return;

		}

		// $trust_source is called by reference and is set to true if the content was retrieved successfully
		$object_data = self::prepareObjectData($activity, $uid, $trust_source);
		if (empty($object_data)) {
			logger('No object data found', LOGGER_DEBUG);
			return;
		}

		if (!$trust_source) {
			logger('No trust for activity type "' . $activity['type'] . '", so we quit now.', LOGGER_DEBUG);
			return;
		}

		switch ($activity['type']) {
			case 'Create':
			case 'Announce':
				ActivityPub\Processor::createItem($object_data, $body);
				break;

			case 'Like':
				ActivityPub\Processor::likeItem($object_data, $body);
				break;

			case 'Dislike':
				ActivityPub\Processor::dislikeItem($object_data, $body);
				break;

			case 'Update':
				if (in_array($object_data['object_type'], ActivityPub::CONTENT_TYPES)) {
					/// @todo
				} elseif (in_array($object_data['object_type'], ActivityPub::ACCOUNT_TYPES)) {
					ActivityPub\Processor::updatePerson($object_data, $body);
				}
				break;

			case 'Delete':
				if ($object_data['object_type'] == 'Tombstone') {
					ActivityPub\Processor::deleteItem($object_data, $body);
				} elseif (in_array($object_data['object_type'], ActivityPub::ACCOUNT_TYPES)) {
					ActivityPub\Processor::deletePerson($object_data, $body);
				}
				break;

			case 'Follow':
				ActivityPub\Processor::followUser($object_data);
				break;

			case 'Accept':
				if ($object_data['object_type'] == 'Follow') {
					ActivityPub\Processor::acceptFollowUser($object_data);
				}
				break;

			case 'Reject':
				if ($object_data['object_type'] == 'Follow') {
					ActivityPub\Processor::rejectFollowUser($object_data);
				}
				break;

			case 'Undo':
				if ($object_data['object_type'] == 'Follow') {
					ActivityPub\Processor::undoFollowUser($object_data);
				} elseif (in_array($object_data['object_type'], ActivityPub::ACTIVITY_TYPES)) {
					ActivityPub\Processor::undoActivity($object_data);
				}
				break;

			default:
				logger('Unknown activity: ' . $activity['type'], LOGGER_DEBUG);
				break;
		}
	}

	/**
	 * 
	 *
	 * @param array $activity
	 * @param $actor
	 *
	 * @return 
	 */
	private static function getReceivers($activity, $actor)
	{
		$receivers = [];

		// When it is an answer, we inherite the receivers from the parent
		$replyto = JsonLD::fetchElement($activity, 'inReplyTo', 'id');
		if (!empty($replyto)) {
			$parents = Item::select(['uid'], ['uri' => $replyto]);
			while ($parent = Item::fetch($parents)) {
				$receivers['uid:' . $parent['uid']] = $parent['uid'];
			}
		}

		if (!empty($actor)) {
			$profile = APContact::getByURL($actor);
			$followers = defaults($profile, 'followers', '');

			logger('Actor: ' . $actor . ' - Followers: ' . $followers, LOGGER_DEBUG);
		} else {
			logger('Empty actor', LOGGER_DEBUG);
			$followers = '';
		}

		foreach (['to', 'cc', 'bto', 'bcc'] as $element) {
			if (empty($activity[$element])) {
				continue;
			}

			// The receiver can be an array or a string
			if (is_string($activity[$element])) {
				$activity[$element] = [$activity[$element]];
			}

			foreach ($activity[$element] as $receiver) {
				if ($receiver == ActivityPub::PUBLIC_COLLECTION) {
					$receivers['uid:0'] = 0;
				}

				if (($receiver == ActivityPub::PUBLIC_COLLECTION) && !empty($actor)) {
					// This will most likely catch all OStatus connections to Mastodon
					$condition = ['alias' => [$actor, normalise_link($actor)], 'rel' => [Contact::SHARING, Contact::FRIEND]
						, 'archive' => false, 'pending' => false];
					$contacts = DBA::select('contact', ['uid'], $condition);
					while ($contact = DBA::fetch($contacts)) {
						if ($contact['uid'] != 0) {
							$receivers['uid:' . $contact['uid']] = $contact['uid'];
						}
					}
					DBA::close($contacts);
				}

				if (in_array($receiver, [$followers, ActivityPub::PUBLIC_COLLECTION]) && !empty($actor)) {
					$condition = ['nurl' => normalise_link($actor), 'rel' => [Contact::SHARING, Contact::FRIEND],
						'network' => Protocol::ACTIVITYPUB, 'archive' => false, 'pending' => false];
					$contacts = DBA::select('contact', ['uid'], $condition);
					while ($contact = DBA::fetch($contacts)) {
						if ($contact['uid'] != 0) {
							$receivers['uid:' . $contact['uid']] = $contact['uid'];
						}
					}
					DBA::close($contacts);
					continue;
				}

				$condition = ['self' => true, 'nurl' => normalise_link($receiver)];
				$contact = DBA::selectFirst('contact', ['uid'], $condition);
				if (!DBA::isResult($contact)) {
					continue;
				}
				$receivers['uid:' . $contact['uid']] = $contact['uid'];
			}
		}

		self::switchContacts($receivers, $actor);

		return $receivers;
	}

	/**
	 * Switches existing contacts to ActivityPub
	 *
	 * @param integer $cid Contact ID
	 * @param integer $uid User ID
	 * @param string $url Profile URL
	 */
	private static function switchContact($cid, $uid, $url)
	{
		$profile = ActivityPub::probeProfile($url);
		if (empty($profile)) {
			return;
		}

		logger('Switch contact ' . $cid . ' (' . $profile['url'] . ') for user ' . $uid . ' to ActivityPub');

		$photo = $profile['photo'];
		unset($profile['photo']);
		unset($profile['baseurl']);

		$profile['nurl'] = normalise_link($profile['url']);
		DBA::update('contact', $profile, ['id' => $cid]);

		Contact::updateAvatar($photo, $uid, $cid);

		// Send a new follow request to be sure that the connection still exists
		if (($uid != 0) && DBA::exists('contact', ['id' => $cid, 'rel' => [Contact::SHARING, Contact::FRIEND]])) {
			ActivityPub\Transmitter::sendActivity('Follow', $profile['url'], $uid);
			logger('Send a new follow request to ' . $profile['url'] . ' for user ' . $uid, LOGGER_DEBUG);
		}
	}

	/**
	 * 
	 *
	 * @param $receivers
	 * @param $actor
	 */
	private static function switchContacts($receivers, $actor)
	{
		if (empty($actor)) {
			return;
		}

		foreach ($receivers as $receiver) {
			$contact = DBA::selectFirst('contact', ['id'], ['uid' => $receiver, 'network' => Protocol::OSTATUS, 'nurl' => normalise_link($actor)]);
			if (DBA::isResult($contact)) {
				self::switchContact($contact['id'], $receiver, $actor);
			}

			$contact = DBA::selectFirst('contact', ['id'], ['uid' => $receiver, 'network' => Protocol::OSTATUS, 'alias' => [normalise_link($actor), $actor]]);
			if (DBA::isResult($contact)) {
				self::switchContact($contact['id'], $receiver, $actor);
			}
		}
	}

	/**
	 * 
	 *
	 * @param $object_data
	 * @param array $activity
	 *
	 * @return 
	 */
	private static function addActivityFields($object_data, $activity)
	{
		if (!empty($activity['published']) && empty($object_data['published'])) {
			$object_data['published'] = $activity['published'];
		}

		if (!empty($activity['updated']) && empty($object_data['updated'])) {
			$object_data['updated'] = $activity['updated'];
		}

		if (!empty($activity['inReplyTo']) && empty($object_data['parent-uri'])) {
			$object_data['parent-uri'] = JsonLD::fetchElement($activity, 'inReplyTo', 'id');
		}

		if (!empty($activity['instrument'])) {
			$object_data['service'] = JsonLD::fetchElement($activity, 'instrument', 'name', 'type', 'Service');
		}
		return $object_data;
	}

	/**
	 * 
	 *
	 * @param $object_id
	 * @param $object
	 * @param $trust_source
	 *
	 * @return 
	 */
	private static function fetchObject($object_id, $object = [], $trust_source = false)
	{
		if (!$trust_source || is_string($object)) {
			$data = ActivityPub::fetchContent($object_id);
			if (empty($data)) {
				logger('Empty content for ' . $object_id . ', check if content is available locally.', LOGGER_DEBUG);
				$data = $object_id;
			} else {
				logger('Fetched content for ' . $object_id, LOGGER_DEBUG);
			}
		} else {
			logger('Using original object for url ' . $object_id, LOGGER_DEBUG);
			$data = $object;
		}

		if (is_string($data)) {
			$item = Item::selectFirst([], ['uri' => $data]);
			if (!DBA::isResult($item)) {
				logger('Object with url ' . $data . ' was not found locally.', LOGGER_DEBUG);
				return false;
			}
			logger('Using already stored item for url ' . $object_id, LOGGER_DEBUG);
			$data = ActivityPub\Transmitter::createNote($item);
		}

		if (empty($data['type'])) {
			logger('Empty type', LOGGER_DEBUG);
			return false;
		}

		if (in_array($data['type'], ActivityPub::CONTENT_TYPES)) {
			return self::processObject($data);
		}

		if ($data['type'] == 'Announce') {
			if (empty($data['object'])) {
				return false;
			}
			return self::fetchObject($data['object']);
		}

		logger('Unhandled object type: ' . $data['type'], LOGGER_DEBUG);
	}

	/**
	 * 
	 *
	 * @param $object
	 *
	 * @return 
	 */
	private static function processObject($object)
	{
		if (empty($object['id'])) {
			return false;
		}

		$object_data = [];
		$object_data['object_type'] = $object['type'];
		$object_data['id'] = $object['id'];

		if (!empty($object['inReplyTo'])) {
			$object_data['reply-to-id'] = JsonLD::fetchElement($object, 'inReplyTo', 'id');
		} else {
			$object_data['reply-to-id'] = $object_data['id'];
		}

		$object_data['published'] = defaults($object, 'published', null);
		$object_data['updated'] = defaults($object, 'updated', $object_data['published']);

		if (empty($object_data['published']) && !empty($object_data['updated'])) {
			$object_data['published'] = $object_data['updated'];
		}

		$actor = JsonLD::fetchElement($object, 'attributedTo', 'id');
		if (empty($actor)) {
			$actor = defaults($object, 'actor', null);
		}

		$object_data['diaspora:guid'] = defaults($object, 'diaspora:guid', null);
		$object_data['owner'] = $object_data['author'] = $actor;
		$object_data['context'] = defaults($object, 'context', null);
		$object_data['conversation'] = defaults($object, 'conversation', null);
		$object_data['sensitive'] = defaults($object, 'sensitive', null);
		$object_data['name'] = defaults($object, 'title', null);
		$object_data['name'] = defaults($object, 'name', $object_data['name']);
		$object_data['summary'] = defaults($object, 'summary', null);
		$object_data['content'] = defaults($object, 'content', null);
		$object_data['source'] = defaults($object, 'source', null);
		$object_data['location'] = JsonLD::fetchElement($object, 'location', 'name', 'type', 'Place');
		$object_data['attachments'] = defaults($object, 'attachment', null);
		$object_data['tags'] = defaults($object, 'tag', null);
		$object_data['service'] = JsonLD::fetchElement($object, 'instrument', 'name', 'type', 'Service');
		$object_data['alternate-url'] = JsonLD::fetchElement($object, 'url', 'href');
		$object_data['receiver'] = self::getReceivers($object, $object_data['owner']);

		// Common object data:

		// Unhandled
		// @context, type, actor, signature, mediaType, duration, replies, icon

		// Also missing: (Defined in the standard, but currently unused)
		// audience, preview, endTime, startTime, generator, image

		// Data in Notes:

		// Unhandled
		// contentMap, announcement_count, announcements, context_id, likes, like_count
		// inReplyToStatusId, shares, quoteUrl, statusnetConversationId

		// Data in video:

		// To-Do?
		// category, licence, language, commentsEnabled

		// Unhandled
		// views, waitTranscoding, state, support, subtitleLanguage
		// likes, dislikes, shares, comments

		return $object_data;
	}
}
