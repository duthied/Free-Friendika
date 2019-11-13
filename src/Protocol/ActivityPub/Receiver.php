<?php
/**
 * @file src/Protocol/ActivityPub/Receiver.php
 */
namespace Friendica\Protocol\ActivityPub;

use Friendica\Database\DBA;
use Friendica\Content\Text\HTML;
use Friendica\Content\Text\Markdown;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Model\Contact;
use Friendica\Model\APContact;
use Friendica\Model\Conversation;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\HTTPSignature;
use Friendica\Util\JsonLD;
use Friendica\Util\LDSignature;
use Friendica\Util\Strings;

/**
 * @brief ActivityPub Receiver Protocol class
 *
 * To-Do:
 * - Undo Announce
 *
 * Check what this is meant to do:
 * - Add
 * - Block
 * - Flag
 * - Remove
 * - Undo Block
 */
class Receiver
{
	const PUBLIC_COLLECTION = 'as:Public';
	const ACCOUNT_TYPES = ['as:Person', 'as:Organization', 'as:Service', 'as:Group', 'as:Application'];
	const CONTENT_TYPES = ['as:Note', 'as:Article', 'as:Video', 'as:Image', 'as:Event'];
	const ACTIVITY_TYPES = ['as:Like', 'as:Dislike', 'as:Accept', 'as:Reject', 'as:TentativeAccept'];

	/**
	 * Checks if the web request is done for the AP protocol
	 *
	 * @return bool is it AP?
	 */
	public static function isRequest()
	{
		return stristr($_SERVER['HTTP_ACCEPT'] ?? '', 'application/activity+json') ||
			stristr($_SERVER['HTTP_ACCEPT'] ?? '', 'application/ld+json');
	}

	/**
	 * Checks incoming message from the inbox
	 *
	 * @param         $body
	 * @param         $header
	 * @param integer $uid User ID
	 * @throws \Exception
	 */
	public static function processInbox($body, $header, $uid)
	{
		$http_signer = HTTPSignature::getSigner($body, $header);
		if (empty($http_signer)) {
			Logger::warning('Invalid HTTP signature, message will be discarded.');
			return;
		} else {
			Logger::info('Valid HTTP signature', ['signer' => $http_signer]);
		}

		$activity = json_decode($body, true);

		if (empty($activity)) {
			Logger::warning('Invalid body.');
			return;
		}

		$ldactivity = JsonLD::compact($activity);

		$actor = JsonLD::fetchElement($ldactivity, 'as:actor', '@id');

		Logger::info('Message for user ' . $uid . ' is from actor ' . $actor);

		if (LDSignature::isSigned($activity)) {
			$ld_signer = LDSignature::getSigner($activity);
			if (empty($ld_signer)) {
				Logger::log('Invalid JSON-LD signature from ' . $actor, Logger::DEBUG);
			}
			if (!empty($ld_signer && ($actor == $http_signer))) {
				Logger::log('The HTTP and the JSON-LD signature belong to ' . $ld_signer, Logger::DEBUG);
				$trust_source = true;
			} elseif (!empty($ld_signer)) {
				Logger::log('JSON-LD signature is signed by ' . $ld_signer, Logger::DEBUG);
				$trust_source = true;
			} elseif ($actor == $http_signer) {
				Logger::log('Bad JSON-LD signature, but HTTP signer fits the actor.', Logger::DEBUG);
				$trust_source = true;
			} else {
				Logger::log('Invalid JSON-LD signature and the HTTP signer is different.', Logger::DEBUG);
				$trust_source = false;
			}
		} elseif ($actor == $http_signer) {
			Logger::log('Trusting post without JSON-LD signature, The actor fits the HTTP signer.', Logger::DEBUG);
			$trust_source = true;
		} else {
			Logger::log('No JSON-LD signature, different actor.', Logger::DEBUG);
			$trust_source = false;
		}

		self::processActivity($ldactivity, $body, $uid, $trust_source);
	}

	/**
	 * Fetches the object type for a given object id
	 *
	 * @param array   $activity
	 * @param string  $object_id Object ID of the the provided object
	 * @param integer $uid       User ID
	 *
	 * @return string with object type
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function fetchObjectType($activity, $object_id, $uid = 0)
	{
		if (!empty($activity['as:object'])) {
			$object_type = JsonLD::fetchElement($activity['as:object'], '@type');
			if (!empty($object_type)) {
				return $object_type;
			}
		}

		if (Item::exists(['uri' => $object_id, 'gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT]])) {
			// We just assume "note" since it doesn't make a difference for the further processing
			return 'as:Note';
		}

		$profile = APContact::getByURL($object_id);
		if (!empty($profile['type'])) {
			return 'as:' . $profile['type'];
		}

		$data = ActivityPub::fetchContent($object_id, $uid);
		if (!empty($data)) {
			$object = JsonLD::compact($data);
			$type = JsonLD::fetchElement($object, '@type');
			if (!empty($type)) {
				return $type;
			}
		}

		return null;
	}

	/**
	 * Prepare the object array
	 *
	 * @param array   $activity
	 * @param integer $uid User ID
	 * @param         $trust_source
	 *
	 * @return array with object data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function prepareObjectData($activity, $uid, &$trust_source)
	{
		$actor = JsonLD::fetchElement($activity, 'as:actor', '@id');
		if (empty($actor)) {
			Logger::log('Empty actor', Logger::DEBUG);
			return [];
		}

		$type = JsonLD::fetchElement($activity, '@type');

		// Fetch all receivers from to, cc, bto and bcc
		$receivers = self::getReceivers($activity, $actor);

		// When it is a delivery to a personal inbox we add that user to the receivers
		if (!empty($uid)) {
			$additional = ['uid:' . $uid => $uid];
			$receivers = array_merge($receivers, $additional);
		} else {
			// We possibly need some user to fetch private content,
			// so we fetch the first out ot the list.
			$uid = self::getFirstUserFromReceivers($receivers);
		}

		Logger::log('Receivers: ' . $uid . ' - ' . json_encode($receivers), Logger::DEBUG);

		$object_id = JsonLD::fetchElement($activity, 'as:object', '@id');
		if (empty($object_id)) {
			Logger::log('No object found', Logger::DEBUG);
			return [];
		}

		if (!is_string($object_id)) {
			Logger::info('Invalid object id', ['object' => $object_id]);
			return [];
		}

		$object_type = self::fetchObjectType($activity, $object_id, $uid);

		// Fetch the content only on activities where this matters
		if (in_array($type, ['as:Create', 'as:Update', 'as:Announce'])) {
			if ($type == 'as:Announce') {
				$trust_source = false;
			}
			$object_data = self::fetchObject($object_id, $activity['as:object'], $trust_source, $uid);
			if (empty($object_data)) {
				Logger::log("Object data couldn't be processed", Logger::DEBUG);
				return [];
			}
			$object_data['object_id'] = $object_id;

			// Test if it is an answer to a mail
			if (DBA::exists('mail', ['uri' => $object_data['reply-to-id']])) {
				$object_data['directmessage'] = true;
			} else {
				$object_data['directmessage'] = JsonLD::fetchElement($activity, 'litepub:directMessage');
			}

			// We had been able to retrieve the object data - so we can trust the source
			$trust_source = true;
		} elseif (in_array($type, array_merge(self::ACTIVITY_TYPES, ['as:Follow'])) && in_array($object_type, self::CONTENT_TYPES)) {
			// Create a mostly empty array out of the activity data (instead of the object).
			// This way we later don't have to check for the existence of ech individual array element.
			$object_data = self::processObject($activity);
			$object_data['name'] = $type;
			$object_data['author'] = JsonLD::fetchElement($activity, 'as:actor', '@id');
			$object_data['object_id'] = $object_id;
			$object_data['object_type'] = ''; // Since we don't fetch the object, we don't know the type
		} elseif (in_array($type, ['as:Add'])) {
			$object_data = [];
			$object_data['id'] = JsonLD::fetchElement($activity, '@id');
			$object_data['target_id'] = JsonLD::fetchElement($activity, 'as:target', '@id');
			$object_data['object_id'] = JsonLD::fetchElement($activity, 'as:object', '@id');
			$object_data['object_type'] = JsonLD::fetchElement($activity['as:object'], '@type');
			$object_data['object_content'] = JsonLD::fetchElement($activity['as:object'], 'as:content', '@type');
		} else {
			$object_data = [];
			$object_data['id'] = JsonLD::fetchElement($activity, '@id');
			$object_data['object_id'] = JsonLD::fetchElement($activity, 'as:object', '@id');
			$object_data['object_actor'] = JsonLD::fetchElement($activity['as:object'], 'as:actor', '@id');
			$object_data['object_object'] = JsonLD::fetchElement($activity['as:object'], 'as:object');
			$object_data['object_type'] = JsonLD::fetchElement($activity['as:object'], '@type');

			// An Undo is done on the object of an object, so we need that type as well
			if ($type == 'as:Undo') {
				$object_data['object_object_type'] = self::fetchObjectType([], $object_data['object_object'], $uid);
			}
		}

		$object_data = self::addActivityFields($object_data, $activity);

		if (empty($object_data['object_type'])) {
			$object_data['object_type'] = $object_type;
		}

		$object_data['type'] = $type;
		$object_data['actor'] = $actor;
		$object_data['item_receiver'] = $receivers;
		$object_data['receiver'] = array_merge($object_data['receiver'] ?? [], $receivers);

		Logger::log('Processing ' . $object_data['type'] . ' ' . $object_data['object_type'] . ' ' . $object_data['id'], Logger::DEBUG);

		return $object_data;
	}

	/**
	 * Fetches the first user id from the receiver array
	 *
	 * @param array $receivers Array with receivers
	 * @return integer user id;
	 */
	public static function getFirstUserFromReceivers($receivers)
	{
		foreach ($receivers as $receiver) {
			if (!empty($receiver)) {
				return $receiver;
			}
		}
		return 0;
	}

	/**
	 * Store the unprocessed data into the conversation table
	 * This has to be done outside the regular function,
	 * since we store everything - not only item posts.
	 *
	 * @param array  $activity Array with activity data
	 * @param string $body     The raw message
	 * @throws \Exception
	 */
	private static function storeConversation($activity, $body)
	{
		if (empty($body) || empty($activity['id'])) {
			return;
		}

		$conversation = [
			'protocol' => Conversation::PARCEL_ACTIVITYPUB,
			'item-uri' => $activity['id'],
			'reply-to-uri' => $activity['reply-to-id'] ?? '',
			'conversation-href' => $activity['context'] ?? '',
			'conversation-uri' => $activity['conversation'] ?? '',
			'source' => $body,
			'received' => DateTimeFormat::utcNow()];

		DBA::insert('conversation', $conversation, true);
	}

	/**
	 * Processes the activity object
	 *
	 * @param array   $activity     Array with activity data
	 * @param string  $body
	 * @param integer $uid          User ID
	 * @param boolean $trust_source Do we trust the source?
	 * @throws \Exception
	 */
	public static function processActivity($activity, $body = '', $uid = null, $trust_source = false)
	{
		$type = JsonLD::fetchElement($activity, '@type');
		if (!$type) {
			Logger::log('Empty type', Logger::DEBUG);
			return;
		}

		if (!JsonLD::fetchElement($activity, 'as:object', '@id')) {
			Logger::log('Empty object', Logger::DEBUG);
			return;
		}

		if (!JsonLD::fetchElement($activity, 'as:actor', '@id')) {
			Logger::log('Empty actor', Logger::DEBUG);
			return;

		}

		// Don't trust the source if "actor" differs from "attributedTo". The content could be forged.
		if ($trust_source && ($type == 'as:Create') && is_array($activity['as:object'])) {
			$actor = JsonLD::fetchElement($activity, 'as:actor', '@id');
			$attributed_to = JsonLD::fetchElement($activity['as:object'], 'as:attributedTo', '@id');
			$trust_source = ($actor == $attributed_to);
			if (!$trust_source) {
				Logger::log('Not trusting actor: ' . $actor . '. It differs from attributedTo: ' . $attributed_to, Logger::DEBUG);
			}
		}

		// $trust_source is called by reference and is set to true if the content was retrieved successfully
		$object_data = self::prepareObjectData($activity, $uid, $trust_source);
		if (empty($object_data)) {
			Logger::log('No object data found', Logger::DEBUG);
			return;
		}

		if (!$trust_source) {
			Logger::log('No trust for activity type "' . $type . '", so we quit now.', Logger::DEBUG);
			return;
		}

		// Only store content related stuff - and no announces, since they possibly overwrite the original content
		if (in_array($object_data['object_type'], self::CONTENT_TYPES) && ($type != 'as:Announce')) {
			self::storeConversation($object_data, $body);
		}

		// Internal flag for thread completion. See Processor.php
		if (!empty($activity['thread-completion'])) {
			$object_data['thread-completion'] = $activity['thread-completion'];
		}

		switch ($type) {
			case 'as:Create':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createItem($object_data);
				}
				break;

			case 'as:Add':
				if ($object_data['object_type'] == 'as:tag') {
					ActivityPub\Processor::addTag($object_data);
				}
				break;

			case 'as:Announce':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					$profile = APContact::getByURL($object_data['actor']);
					// Reshared posts from persons appear as summary at the bottom
					// If this isn't set, then a single reshare appears on top. This is used for groups.
					$object_data['thread-completion'] = ($profile['type'] != 'Group');

					ActivityPub\Processor::createItem($object_data);

					// Add the bottom reshare information only for persons
					if ($profile['type'] != 'Group') {
						$announce_object_data = self::processObject($activity);
						$announce_object_data['name'] = $type;
						$announce_object_data['author'] = JsonLD::fetchElement($activity, 'as:actor', '@id');
						$announce_object_data['object_id'] = $object_data['object_id'];
						$announce_object_data['object_type'] = $object_data['object_type'];

						ActivityPub\Processor::createActivity($announce_object_data, Activity::ANNOUNCE);
					}
				}
				break;

			case 'as:Like':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, Activity::LIKE);
				}
				break;

			case 'as:Dislike':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, Activity::DISLIKE);
				}
				break;

			case 'as:TentativeAccept':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, Activity::ATTENDMAYBE);
				}
				break;

			case 'as:Update':
				if (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::updateItem($object_data);
				} elseif (in_array($object_data['object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::updatePerson($object_data);
				}
				break;

			case 'as:Delete':
				if ($object_data['object_type'] == 'as:Tombstone') {
					ActivityPub\Processor::deleteItem($object_data);
				} elseif (in_array($object_data['object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::deletePerson($object_data);
				}
				break;

			case 'as:Follow':
				if (in_array($object_data['object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::followUser($object_data);
				} elseif (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					$object_data['reply-to-id'] = $object_data['object_id'];
					ActivityPub\Processor::createActivity($object_data, Activity::FOLLOW);
				}
				break;

			case 'as:Accept':
				if ($object_data['object_type'] == 'as:Follow') {
					ActivityPub\Processor::acceptFollowUser($object_data);
				} elseif (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, Activity::ATTEND);
				}
				break;

			case 'as:Reject':
				if ($object_data['object_type'] == 'as:Follow') {
					ActivityPub\Processor::rejectFollowUser($object_data);
				} elseif (in_array($object_data['object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::createActivity($object_data, Activity::ATTENDNO);
				}
				break;

			case 'as:Undo':
				if (($object_data['object_type'] == 'as:Follow') &&
					in_array($object_data['object_object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::undoFollowUser($object_data);
				} elseif (($object_data['object_type'] == 'as:Accept') &&
					in_array($object_data['object_object_type'], self::ACCOUNT_TYPES)) {
					ActivityPub\Processor::rejectFollowUser($object_data);
				} elseif (in_array($object_data['object_type'], self::ACTIVITY_TYPES) &&
					in_array($object_data['object_object_type'], self::CONTENT_TYPES)) {
					ActivityPub\Processor::undoActivity($object_data);
				}
				break;

			default:
				Logger::log('Unknown activity: ' . $type . ' ' . $object_data['object_type'], Logger::DEBUG);
				break;
		}
	}

	/**
	 * Fetch the receiver list from an activity array
	 *
	 * @param array  $activity
	 * @param string $actor
	 * @param array  $tags
	 *
	 * @return array with receivers (user id)
	 * @throws \Exception
	 */
	private static function getReceivers($activity, $actor, $tags = [])
	{
		$receivers = [];

		// When it is an answer, we inherite the receivers from the parent
		$replyto = JsonLD::fetchElement($activity, 'as:inReplyTo', '@id');
		if (!empty($replyto)) {
			$parents = Item::select(['uid'], ['uri' => $replyto]);
			while ($parent = Item::fetch($parents)) {
				$receivers['uid:' . $parent['uid']] = $parent['uid'];
			}
		}

		if (!empty($actor)) {
			$profile = APContact::getByURL($actor);
			$followers = $profile['followers'] ?? '';

			Logger::log('Actor: ' . $actor . ' - Followers: ' . $followers, Logger::DEBUG);
		} else {
			Logger::log('Empty actor', Logger::DEBUG);
			$followers = '';
		}

		foreach (['as:to', 'as:cc', 'as:bto', 'as:bcc'] as $element) {
			$receiver_list = JsonLD::fetchElementArray($activity, $element, '@id');
			if (empty($receiver_list)) {
				continue;
			}

			foreach ($receiver_list as $receiver) {
				if ($receiver == self::PUBLIC_COLLECTION) {
					$receivers['uid:0'] = 0;
				}

				if (($receiver == self::PUBLIC_COLLECTION) && !empty($actor)) {
					// This will most likely catch all OStatus connections to Mastodon
					$condition = ['alias' => [$actor, Strings::normaliseLink($actor)], 'rel' => [Contact::SHARING, Contact::FRIEND]
						, 'archive' => false, 'pending' => false];
					$contacts = DBA::select('contact', ['uid'], $condition);
					while ($contact = DBA::fetch($contacts)) {
						if ($contact['uid'] != 0) {
							$receivers['uid:' . $contact['uid']] = $contact['uid'];
						}
					}
					DBA::close($contacts);
				}

				if (in_array($receiver, [$followers, self::PUBLIC_COLLECTION]) && !empty($actor)) {
					$receivers = array_merge($receivers, self::getReceiverForActor($actor, $tags));
					continue;
				}

				// Fetching all directly addressed receivers
				$condition = ['self' => true, 'nurl' => Strings::normaliseLink($receiver)];
				$contact = DBA::selectFirst('contact', ['uid', 'contact-type'], $condition);
				if (!DBA::isResult($contact)) {
					continue;
				}

				// Check if the potential receiver is following the actor
				// Exception: The receiver is targetted via "to" or this is a comment
				if ((($element != 'as:to') && empty($replyto)) || ($contact['contact-type'] == Contact::TYPE_COMMUNITY)) {
					$networks = Protocol::FEDERATED;
					$condition = ['nurl' => Strings::normaliseLink($actor), 'rel' => [Contact::SHARING, Contact::FRIEND],
						'network' => $networks, 'archive' => false, 'pending' => false, 'uid' => $contact['uid']];

					// Forum posts are only accepted from forum contacts
					if ($contact['contact-type'] == Contact::TYPE_COMMUNITY) {
						$condition['rel'] = [Contact::SHARING, Contact::FRIEND, Contact::FOLLOWER];
					}

					if (!DBA::exists('contact', $condition)) {
						continue;
					}
				}

				$receivers['uid:' . $contact['uid']] = $contact['uid'];
			}
		}

		self::switchContacts($receivers, $actor);

		return $receivers;
	}

	/**
	 * Fetch the receiver list of a given actor
	 *
	 * @param string $actor
	 * @param array  $tags
	 *
	 * @return array with receivers (user id)
	 * @throws \Exception
	 */
	public static function getReceiverForActor($actor, $tags)
	{
		$receivers = [];
		$networks = Protocol::FEDERATED;
		$condition = ['nurl' => Strings::normaliseLink($actor), 'rel' => [Contact::SHARING, Contact::FRIEND, Contact::FOLLOWER],
			'network' => $networks, 'archive' => false, 'pending' => false];
		$contacts = DBA::select('contact', ['uid', 'rel'], $condition);
		while ($contact = DBA::fetch($contacts)) {
			if (self::isValidReceiverForActor($contact, $actor, $tags)) {
				$receivers['uid:' . $contact['uid']] = $contact['uid'];
			}
		}
		DBA::close($contacts);
		return $receivers;
	}

	/**
	 * Tests if the contact is a valid receiver for this actor
	 *
	 * @param array  $contact
	 * @param string $actor
	 * @param array  $tags
	 *
	 * @return bool with receivers (user id)
	 * @throws \Exception
	 */
	private static function isValidReceiverForActor($contact, $actor, $tags)
	{
		// Public contacts are no valid receiver
		if ($contact['uid'] == 0) {
			return false;
		}

		// Are we following the contact? Then this is a valid receiver
		if (in_array($contact['rel'], [Contact::SHARING, Contact::FRIEND])) {
			return true;
		}

		// When the possible receiver isn't a community, then it is no valid receiver
		$owner = User::getOwnerDataById($contact['uid']);
		if (empty($owner) || ($owner['contact-type'] != Contact::TYPE_COMMUNITY)) {
			return false;
		}

		// Is the community account tagged?
		foreach ($tags as $tag) {
			if ($tag['type'] != 'Mention') {
				continue;
			}

			if ($tag['href'] == $owner['url']) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Switches existing contacts to ActivityPub
	 *
	 * @param integer $cid Contact ID
	 * @param integer $uid User ID
	 * @param string  $url Profile URL
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function switchContact($cid, $uid, $url)
	{
		if (DBA::exists('contact', ['id' => $cid, 'network' => Protocol::ACTIVITYPUB])) {
			Logger::info('Contact is already ActivityPub', ['id' => $cid, 'uid' => $uid, 'url' => $url]);
			return;
		}

		if (Contact::updateFromProbe($cid, '', true)) {
			Logger::info('Update was successful', ['id' => $cid, 'uid' => $uid, 'url' => $url]);
		}

		// Send a new follow request to be sure that the connection still exists
		if (($uid != 0) && DBA::exists('contact', ['id' => $cid, 'rel' => [Contact::SHARING, Contact::FRIEND], 'network' => Protocol::ACTIVITYPUB])) {
			Logger::info('Contact had been switched to ActivityPub. Sending a new follow request.', ['uid' => $uid, 'url' => $url]);
			ActivityPub\Transmitter::sendActivity('Follow', $url, $uid);
		}
	}

	/**
	 *
	 *
	 * @param $receivers
	 * @param $actor
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function switchContacts($receivers, $actor)
	{
		if (empty($actor)) {
			return;
		}

		foreach ($receivers as $receiver) {
			$contact = DBA::selectFirst('contact', ['id'], ['uid' => $receiver, 'network' => Protocol::OSTATUS, 'nurl' => Strings::normaliseLink($actor)]);
			if (DBA::isResult($contact)) {
				self::switchContact($contact['id'], $receiver, $actor);
			}

			$contact = DBA::selectFirst('contact', ['id'], ['uid' => $receiver, 'network' => Protocol::OSTATUS, 'alias' => [Strings::normaliseLink($actor), $actor]]);
			if (DBA::isResult($contact)) {
				self::switchContact($contact['id'], $receiver, $actor);
			}
		}
	}

	/**
	 *
	 *
	 * @param       $object_data
	 * @param array $activity
	 *
	 * @return mixed
	 */
	private static function addActivityFields($object_data, $activity)
	{
		if (!empty($activity['published']) && empty($object_data['published'])) {
			$object_data['published'] = JsonLD::fetchElement($activity, 'as:published', '@value');
		}

		if (!empty($activity['diaspora:guid']) && empty($object_data['diaspora:guid'])) {
			$object_data['diaspora:guid'] = JsonLD::fetchElement($activity, 'diaspora:guid', '@value');
		}

		$object_data['service'] = JsonLD::fetchElement($activity, 'as:instrument', 'as:name', '@type', 'as:Service');
		$object_data['service'] = JsonLD::fetchElement($object_data, 'service', '@value');

		return $object_data;
	}

	/**
	 * Fetches the object data from external ressources if needed
	 *
	 * @param string  $object_id    Object ID of the the provided object
	 * @param array   $object       The provided object array
	 * @param boolean $trust_source Do we trust the provided object?
	 * @param integer $uid          User ID for the signature that we use to fetch data
	 *
	 * @return array|false with trusted and valid object data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function fetchObject(string $object_id, array $object = [], bool $trust_source = false, int $uid = 0)
	{
		// By fetching the type we check if the object is complete.
		$type = JsonLD::fetchElement($object, '@type');

		if (!$trust_source || empty($type)) {
			$data = ActivityPub::fetchContent($object_id, $uid);
			if (!empty($data)) {
				$object = JsonLD::compact($data);
				Logger::log('Fetched content for ' . $object_id, Logger::DEBUG);
			} else {
				Logger::log('Empty content for ' . $object_id . ', check if content is available locally.', Logger::DEBUG);

				$item = Item::selectFirst([], ['uri' => $object_id]);
				if (!DBA::isResult($item)) {
					Logger::log('Object with url ' . $object_id . ' was not found locally.', Logger::DEBUG);
					return false;
				}
				Logger::log('Using already stored item for url ' . $object_id, Logger::DEBUG);
				$data = ActivityPub\Transmitter::createNote($item);
				$object = JsonLD::compact($data);
			}
		} else {
			Logger::log('Using original object for url ' . $object_id, Logger::DEBUG);
		}

		$type = JsonLD::fetchElement($object, '@type');

		if (empty($type)) {
			Logger::log('Empty type', Logger::DEBUG);
			return false;
		}

		if (in_array($type, self::CONTENT_TYPES)) {
			return self::processObject($object);
		}

		if ($type == 'as:Announce') {
			$object_id = JsonLD::fetchElement($object, 'object', '@id');
			if (empty($object_id) || !is_string($object_id)) {
				return false;
			}
			return self::fetchObject($object_id, [], false, $uid);
		}

		Logger::log('Unhandled object type: ' . $type, Logger::DEBUG);
		return false;
	}

	/**
	 * Convert tags from JSON-LD format into a simplified format
	 *
	 * @param array $tags Tags in JSON-LD format
	 *
	 * @return array with tags in a simplified format
	 */
	private static function processTags($tags)
	{
		$taglist = [];

		if (empty($tags)) {
			return [];
		}

		foreach ($tags as $tag) {
			if (empty($tag)) {
				continue;
			}

			$element = ['type' => str_replace('as:', '', JsonLD::fetchElement($tag, '@type')),
				'href' => JsonLD::fetchElement($tag, 'as:href', '@id'),
				'name' => JsonLD::fetchElement($tag, 'as:name', '@value')];

			if (empty($element['type'])) {
				continue;
			}

			$taglist[] = $element;
		}
		return $taglist;
	}

	/**
	 * Convert emojis from JSON-LD format into a simplified format
	 *
	 * @param $emojis
	 * @return array with emojis in a simplified format
	 */
	private static function processEmojis($emojis)
	{
		$emojilist = [];

		if (empty($emojis)) {
			return [];
		}

		foreach ($emojis as $emoji) {
			if (empty($emoji) || (JsonLD::fetchElement($emoji, '@type') != 'toot:Emoji') || empty($emoji['as:icon'])) {
				continue;
			}

			$url = JsonLD::fetchElement($emoji['as:icon'], 'as:url', '@id');
			$element = ['name' => JsonLD::fetchElement($emoji, 'as:name', '@value'),
				'href' => $url];

			$emojilist[] = $element;
		}
		return $emojilist;
	}

	/**
	 * Convert attachments from JSON-LD format into a simplified format
	 *
	 * @param array $attachments Attachments in JSON-LD format
	 *
	 * @return array with attachmants in a simplified format
	 */
	private static function processAttachments($attachments)
	{
		$attachlist = [];

		if (empty($attachments)) {
			return [];
		}

		foreach ($attachments as $attachment) {
			if (empty($attachment)) {
				continue;
			}

			$attachlist[] = ['type' => str_replace('as:', '', JsonLD::fetchElement($attachment, '@type')),
				'mediaType' => JsonLD::fetchElement($attachment, 'as:mediaType', '@value'),
				'name' => JsonLD::fetchElement($attachment, 'as:name', '@value'),
				'url' => JsonLD::fetchElement($attachment, 'as:url', '@id')];
		}
		return $attachlist;
	}

	/**
	 * Fetch the original source or content with the "language" Markdown or HTML
	 *
	 * @param array $object
	 * @param array $object_data
	 *
	 * @return array
	 * @throws \Exception
	 */
	private static function getSource($object, $object_data)
	{
		$object_data['source'] = JsonLD::fetchElement($object, 'as:source', 'as:content', 'as:mediaType', 'text/bbcode');
		$object_data['source'] = JsonLD::fetchElement($object_data, 'source', '@value');
		if (!empty($object_data['source'])) {
			return $object_data;
		}

		$object_data['source'] = JsonLD::fetchElement($object, 'as:source', 'as:content', 'as:mediaType', 'text/markdown');
		$object_data['source'] = JsonLD::fetchElement($object_data, 'source', '@value');
		if (!empty($object_data['source'])) {
			$object_data['source'] = Markdown::toBBCode($object_data['source']);
			return $object_data;
		}

		$object_data['source'] = JsonLD::fetchElement($object, 'as:source', 'as:content', 'as:mediaType', 'text/html');
		$object_data['source'] = JsonLD::fetchElement($object_data, 'source', '@value');
		if (!empty($object_data['source'])) {
			$object_data['source'] = HTML::toBBCode($object_data['source']);
			return $object_data;
		}

		$markdown = JsonLD::fetchElement($object, 'as:content', '@value', '@language', 'text/markdown');
		if (!empty($markdown)) {
			$object_data['source'] = Markdown::toBBCode($markdown);
			return $object_data;
		}

		$html = JsonLD::fetchElement($object, 'as:content', '@value', '@language', 'text/html');
		if (!empty($html)) {
			$object_data['source'] = HTML::toBBCode($markdown);
			return $object_data;
		}

		return $object_data;
	}

	/**
	 * Fetches data from the object part of an activity
	 *
	 * @param array $object
	 *
	 * @return array
	 * @throws \Exception
	 */
	private static function processObject($object)
	{
		if (!JsonLD::fetchElement($object, '@id')) {
			return false;
		}

		$object_data = [];
		$object_data['object_type'] = JsonLD::fetchElement($object, '@type');
		$object_data['id'] = JsonLD::fetchElement($object, '@id');
		$object_data['reply-to-id'] = JsonLD::fetchElement($object, 'as:inReplyTo', '@id');

		// An empty "id" field is translated to "./" by the compactor, so we have to check for this content
		if (empty($object_data['reply-to-id']) || ($object_data['reply-to-id'] == './')) {
			$object_data['reply-to-id'] = $object_data['id'];
		}

		$object_data['published'] = JsonLD::fetchElement($object, 'as:published', '@value');
		$object_data['updated'] = JsonLD::fetchElement($object, 'as:updated', '@value');

		if (empty($object_data['updated'])) {
			$object_data['updated'] = $object_data['published'];
		}

		if (empty($object_data['published']) && !empty($object_data['updated'])) {
			$object_data['published'] = $object_data['updated'];
		}

		$actor = JsonLD::fetchElement($object, 'as:attributedTo', '@id');
		if (empty($actor)) {
			$actor = JsonLD::fetchElement($object, 'as:actor', '@id');
		}

		$object_data['diaspora:guid'] = JsonLD::fetchElement($object, 'diaspora:guid', '@value');
		$object_data['diaspora:comment'] = JsonLD::fetchElement($object, 'diaspora:comment', '@value');
		$object_data['diaspora:like'] = JsonLD::fetchElement($object, 'diaspora:like', '@value');
		$object_data['actor'] = $object_data['author'] = $actor;
		$object_data['context'] = JsonLD::fetchElement($object, 'as:context', '@id');
		$object_data['conversation'] = JsonLD::fetchElement($object, 'ostatus:conversation', '@id');
		$object_data['sensitive'] = JsonLD::fetchElement($object, 'as:sensitive');
		$object_data['name'] = JsonLD::fetchElement($object, 'as:name', '@value');
		$object_data['summary'] = JsonLD::fetchElement($object, 'as:summary', '@value');
		$object_data['content'] = JsonLD::fetchElement($object, 'as:content', '@value');
		$object_data = self::getSource($object, $object_data);
		$object_data['start-time'] = JsonLD::fetchElement($object, 'as:startTime', '@value');
		$object_data['end-time'] = JsonLD::fetchElement($object, 'as:endTime', '@value');
		$object_data['location'] = JsonLD::fetchElement($object, 'as:location', 'as:name', '@type', 'as:Place');
		$object_data['location'] = JsonLD::fetchElement($object_data, 'location', '@value');
		$object_data['latitude'] = JsonLD::fetchElement($object, 'as:location', 'as:latitude', '@type', 'as:Place');
		$object_data['latitude'] = JsonLD::fetchElement($object_data, 'latitude', '@value');
		$object_data['longitude'] = JsonLD::fetchElement($object, 'as:location', 'as:longitude', '@type', 'as:Place');
		$object_data['longitude'] = JsonLD::fetchElement($object_data, 'longitude', '@value');
		$object_data['attachments'] = self::processAttachments(JsonLD::fetchElementArray($object, 'as:attachment'));
		$object_data['tags'] = self::processTags(JsonLD::fetchElementArray($object, 'as:tag'));
		$object_data['emojis'] = self::processEmojis(JsonLD::fetchElementArray($object, 'as:tag', 'toot:Emoji'));
		$object_data['generator'] = JsonLD::fetchElement($object, 'as:generator', 'as:name', '@type', 'as:Application');
		$object_data['generator'] = JsonLD::fetchElement($object_data, 'generator', '@value');
		$object_data['alternate-url'] = JsonLD::fetchElement($object, 'as:url', '@id');

		// Special treatment for Hubzilla links
		if (is_array($object_data['alternate-url'])) {
			$object_data['alternate-url'] = JsonLD::fetchElement($object_data['alternate-url'], 'as:href', '@id');

			if (!is_string($object_data['alternate-url'])) {
				$object_data['alternate-url'] = JsonLD::fetchElement($object['as:url'], 'as:href', '@id');
			}
		}

		$object_data['receiver'] = self::getReceivers($object, $object_data['actor'], $object_data['tags']);

		// Common object data:

		// Unhandled
		// @context, type, actor, signature, mediaType, duration, replies, icon

		// Also missing: (Defined in the standard, but currently unused)
		// audience, preview, endTime, startTime, image

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
