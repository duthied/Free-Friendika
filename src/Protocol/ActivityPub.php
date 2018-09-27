<?php
/**
 * @file src/Protocol/ActivityPub.php
 */
namespace Friendica\Protocol;

use Friendica\Database\DBA;
use Friendica\Core\System;
use Friendica\BaseObject;
use Friendica\Util\Network;
use Friendica\Util\HTTPSignature;
use Friendica\Core\Protocol;
use Friendica\Model\Conversation;
use Friendica\Model\Contact;
use Friendica\Model\APContact;
use Friendica\Model\Item;
use Friendica\Model\Profile;
use Friendica\Model\Term;
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Crypto;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Util\JsonLD;
use Friendica\Util\LDSignature;
use Friendica\Core\Config;

/**
 * @brief ActivityPub Protocol class
 * The ActivityPub Protocol is a message exchange protocol defined by the W3C.
 * https://www.w3.org/TR/activitypub/
 * https://www.w3.org/TR/activitystreams-core/
 * https://www.w3.org/TR/activitystreams-vocabulary/
 *
 * https://blog.joinmastodon.org/2018/06/how-to-implement-a-basic-activitypub-server/
 * https://blog.joinmastodon.org/2018/07/how-to-make-friends-and-verify-requests/
 *
 * Digest: https://tools.ietf.org/html/rfc5843
 * https://tools.ietf.org/html/draft-cavage-http-signatures-10#ref-15
 *
 * Mastodon implementation of supported activities:
 * https://github.com/tootsuite/mastodon/blob/master/app/lib/activitypub/activity.rb#L26
 *
 * To-do:
 *
 * Receiver:
 * - Update Note
 * - Delete Note
 * - Delete Person
 * - Undo Announce
 * - Reject Follow
 * - Undo Accept
 * - Undo Follow
 * - Add
 * - Create Image
 * - Create Video
 * - Event
 * - Remove
 * - Block
 * - Flag
 *
 * Transmitter:
 * - Announce
 * - Undo Announce
 * - Update Person
 * - Reject Follow
 * - Event
 *
 * General:
 * - Attachments
 * - nsfw (sensitive)
 * - Queueing unsucessful deliveries
 * - Polling the outboxes for missing content?
 * - Possibly using the LD-JSON parser
 */
class ActivityPub
{
	const PUBLIC = 'https://www.w3.org/ns/activitystreams#Public';
	const CONTEXT = ['https://www.w3.org/ns/activitystreams', 'https://w3id.org/security/v1',
		['vcard' => 'http://www.w3.org/2006/vcard/ns#',
		'diaspora' => 'https://diasporafoundation.org/ns/',
		'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers',
		'sensitive' => 'as:sensitive', 'Hashtag' => 'as:Hashtag']];

	/**
	 * @brief Checks if the web request is done for the AP protocol
	 *
	 * @return is it AP?
	 */
	public static function isRequest()
	{
		return stristr(defaults($_SERVER, 'HTTP_ACCEPT', ''), 'application/activity+json') ||
			stristr(defaults($_SERVER, 'HTTP_ACCEPT', ''), 'application/ld+json');
	}

	/**
	 * @brief collects the lost of followers of the given owner
	 *
	 * @param array $owner Owner array
	 * @param integer $page Page number
	 *
	 * @return array of owners
	 */
	public static function getFollowers($owner, $page = null)
	{
		$condition = ['rel' => [Contact::FOLLOWER, Contact::FRIEND], 'network' => Protocol::NATIVE_SUPPORT, 'uid' => $owner['uid'],
			'self' => false, 'hidden' => false, 'archive' => false, 'pending' => false];
		$count = DBA::count('contact', $condition);

		$data = ['@context' => self::CONTEXT];
		$data['id'] = System::baseUrl() . '/followers/' . $owner['nickname'];
		$data['type'] = 'OrderedCollection';
		$data['totalItems'] = $count;

		// When we hide our friends we will only show the pure number but don't allow more.
		$profile = Profile::getProfileForUser($owner['uid']);
		if (!empty($profile['hide-friends'])) {
			return $data;
		}

		if (empty($page)) {
			$data['first'] = System::baseUrl() . '/followers/' . $owner['nickname'] . '?page=1';
		} else {
			$list = [];

			$contacts = DBA::select('contact', ['url'], $condition, ['limit' => [($page - 1) * 100, 100]]);
			while ($contact = DBA::fetch($contacts)) {
				$list[] = $contact['url'];
			}

			if (!empty($list)) {
				$data['next'] = System::baseUrl() . '/followers/' . $owner['nickname'] . '?page=' . ($page + 1);
			}

			$data['partOf'] = System::baseUrl() . '/followers/' . $owner['nickname'];

			$data['orderedItems'] = $list;
		}

		return $data;
	}

	/**
	 * @brief Create list of following contacts
	 *
	 * @param array $owner Owner array
	 * @param integer $page Page numbe
	 *
	 * @return array of following contacts
	 */
	public static function getFollowing($owner, $page = null)
	{
		$condition = ['rel' => [Contact::SHARING, Contact::FRIEND], 'network' => Protocol::NATIVE_SUPPORT, 'uid' => $owner['uid'],
			'self' => false, 'hidden' => false, 'archive' => false, 'pending' => false];
		$count = DBA::count('contact', $condition);

		$data = ['@context' => self::CONTEXT];
		$data['id'] = System::baseUrl() . '/following/' . $owner['nickname'];
		$data['type'] = 'OrderedCollection';
		$data['totalItems'] = $count;

		// When we hide our friends we will only show the pure number but don't allow more.
		$profile = Profile::getProfileForUser($owner['uid']);
		if (!empty($profile['hide-friends'])) {
			return $data;
		}

		if (empty($page)) {
			$data['first'] = System::baseUrl() . '/following/' . $owner['nickname'] . '?page=1';
		} else {
			$list = [];

			$contacts = DBA::select('contact', ['url'], $condition, ['limit' => [($page - 1) * 100, 100]]);
			while ($contact = DBA::fetch($contacts)) {
				$list[] = $contact['url'];
			}

			if (!empty($list)) {
				$data['next'] = System::baseUrl() . '/following/' . $owner['nickname'] . '?page=' . ($page + 1);
			}

			$data['partOf'] = System::baseUrl() . '/following/' . $owner['nickname'];

			$data['orderedItems'] = $list;
		}

		return $data;
	}

	/**
	 * @brief Public posts for the given owner
	 *
	 * @param array $owner Owner array
	 * @param integer $page Page numbe
	 *
	 * @return array of posts
	 */
	public static function getOutbox($owner, $page = null)
	{
		$public_contact = Contact::getIdForURL($owner['url'], 0, true);

		$condition = ['uid' => $owner['uid'], 'contact-id' => $owner['id'], 'author-id' => $public_contact,
			'wall' => true, 'private' => false, 'gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT],
			'deleted' => false, 'visible' => true];
		$count = DBA::count('item', $condition);

		$data = ['@context' => self::CONTEXT];
		$data['id'] = System::baseUrl() . '/outbox/' . $owner['nickname'];
		$data['type'] = 'OrderedCollection';
		$data['totalItems'] = $count;

		if (empty($page)) {
			$data['first'] = System::baseUrl() . '/outbox/' . $owner['nickname'] . '?page=1';
		} else {
			$list = [];

			$condition['parent-network'] = Protocol::NATIVE_SUPPORT;

			$items = Item::select(['id'], $condition, ['limit' => [($page - 1) * 20, 20], 'order' => ['created' => true]]);
			while ($item = Item::fetch($items)) {
				$object = self::createObjectFromItemID($item['id']);
				unset($object['@context']);
				$list[] = $object;
			}

			if (!empty($list)) {
				$data['next'] = System::baseUrl() . '/outbox/' . $owner['nickname'] . '?page=' . ($page + 1);
			}

			$data['partOf'] = System::baseUrl() . '/outbox/' . $owner['nickname'];

			$data['orderedItems'] = $list;
		}

		return $data;
	}

	/**
	 * Return the ActivityPub profile of the given user
	 *
	 * @param integer $uid User ID
	 * @return profile array
	 */
	public static function profile($uid)
	{
		$accounttype = ['Person', 'Organization', 'Service', 'Group', 'Application'];
		$condition = ['uid' => $uid, 'blocked' => false, 'account_expired' => false,
			'account_removed' => false, 'verified' => true];
		$fields = ['guid', 'nickname', 'pubkey', 'account-type', 'page-flags'];
		$user = DBA::selectFirst('user', $fields, $condition);
		if (!DBA::isResult($user)) {
			return [];
		}

		$fields = ['locality', 'region', 'country-name'];
		$profile = DBA::selectFirst('profile', $fields, ['uid' => $uid, 'is-default' => true]);
		if (!DBA::isResult($profile)) {
			return [];
		}

		$fields = ['name', 'url', 'location', 'about', 'avatar'];
		$contact = DBA::selectFirst('contact', $fields, ['uid' => $uid, 'self' => true]);
		if (!DBA::isResult($contact)) {
			return [];
		}

		$data = ['@context' => self::CONTEXT];
		$data['id'] = $contact['url'];
		$data['diaspora:guid'] = $user['guid'];
		$data['type'] = $accounttype[$user['account-type']];
		$data['following'] = System::baseUrl() . '/following/' . $user['nickname'];
		$data['followers'] = System::baseUrl() . '/followers/' . $user['nickname'];
		$data['inbox'] = System::baseUrl() . '/inbox/' . $user['nickname'];
		$data['outbox'] = System::baseUrl() . '/outbox/' . $user['nickname'];
		$data['preferredUsername'] = $user['nickname'];
		$data['name'] = $contact['name'];
		$data['vcard:hasAddress'] = ['@type' => 'vcard:Home', 'vcard:country-name' => $profile['country-name'],
			'vcard:region' => $profile['region'], 'vcard:locality' => $profile['locality']];
		$data['summary'] = $contact['about'];
		$data['url'] = $contact['url'];
		$data['manuallyApprovesFollowers'] = in_array($user['page-flags'], [Contact::PAGE_NORMAL, Contact::PAGE_PRVGROUP]);
		$data['publicKey'] = ['id' => $contact['url'] . '#main-key',
			'owner' => $contact['url'],
			'publicKeyPem' => $user['pubkey']];
		$data['endpoints'] = ['sharedInbox' => System::baseUrl() . '/inbox'];
		$data['icon'] = ['type' => 'Image',
			'url' => $contact['avatar']];

		// tags: https://kitty.town/@inmysocks/100656097926961126.json
		return $data;
	}

	/**
	 * @brief Returns an array with permissions of a given item array
	 *
	 * @param array $item
	 *
	 * @return array with permissions
	 */
	private static function fetchPermissionBlockFromConversation($item)
	{
		if (empty($item['thr-parent'])) {
			return [];
		}

		$condition = ['item-uri' => $item['thr-parent'], 'protocol' => Conversation::PARCEL_ACTIVITYPUB];
		$conversation = DBA::selectFirst('conversation', ['source'], $condition);
		if (!DBA::isResult($conversation)) {
			return [];
		}

		$activity = json_decode($conversation['source'], true);

		$actor = JsonLD::fetchElement($activity, 'actor', 'id');
		$profile = APContact::getProfileByURL($actor);

		$item_profile = APContact::getProfileByURL($item['author-link']);
		$exclude[] = $item['author-link'];

		if ($item['gravity'] == GRAVITY_PARENT) {
			$exclude[] = $item['owner-link'];
		}

		$permissions['to'][] = $actor;

		$elements = ['to', 'cc', 'bto', 'bcc'];
		foreach ($elements as $element) {
			if (empty($activity[$element])) {
				continue;
			}
			if (is_string($activity[$element])) {
				$activity[$element] = [$activity[$element]];
			}

			foreach ($activity[$element] as $receiver) {
				if ($receiver == $profile['followers'] && !empty($item_profile['followers'])) {
					$receiver = $item_profile['followers'];
				}
				if (!in_array($receiver, $exclude)) {
					$permissions[$element][] = $receiver;
				}
			}
		}
		return $permissions;
	}

	/**
	 * @brief Creates an array of permissions from an item thread
	 *
	 * @param array $item
	 *
	 * @return permission array
	 */
	public static function createPermissionBlockForItem($item)
	{
		$data = ['to' => [], 'cc' => []];

		$data = array_merge($data, self::fetchPermissionBlockFromConversation($item));

		$actor_profile = APContact::getProfileByURL($item['author-link']);

		$terms = Term::tagArrayFromItemId($item['id']);

		$contacts[$item['author-link']] = $item['author-link'];

		if (!$item['private']) {
			$data['to'][] = self::PUBLIC;
			if (!empty($actor_profile['followers'])) {
				$data['cc'][] = $actor_profile['followers'];
			}

			foreach ($terms as $term) {
				if ($term['type'] != TERM_MENTION) {
					continue;
				}
				$profile = APContact::getProfileByURL($term['url'], false);
				if (!empty($profile) && empty($contacts[$profile['url']])) {
					$data['cc'][] = $profile['url'];
					$contacts[$profile['url']] = $profile['url'];
				}
			}
		} else {
			$receiver_list = Item::enumeratePermissions($item);

			$mentioned = [];

			foreach ($terms as $term) {
				if ($term['type'] != TERM_MENTION) {
					continue;
				}
				$cid = Contact::getIdForURL($term['url'], $item['uid']);
				if (!empty($cid) && in_array($cid, $receiver_list)) {
					$contact = DBA::selectFirst('contact', ['url'], ['id' => $cid, 'network' => Protocol::ACTIVITYPUB]);
					$data['to'][] = $contact['url'];
					$contacts[$contact['url']] = $contact['url'];
				}
			}

			foreach ($receiver_list as $receiver) {
				$contact = DBA::selectFirst('contact', ['url'], ['id' => $receiver, 'network' => Protocol::ACTIVITYPUB]);
				if (empty($contacts[$contact['url']])) {
					$data['cc'][] = $contact['url'];
					$contacts[$contact['url']] = $contact['url'];
				}
			}
		}

		$parents = Item::select(['id', 'author-link', 'owner-link', 'gravity'], ['parent' => $item['parent']]);
		while ($parent = Item::fetch($parents)) {
			// Don't include data from future posts
			if ($parent['id'] >= $item['id']) {
				continue;
			}

			$profile = APContact::getProfileByURL($parent['author-link'], false);
			if (!empty($profile) && empty($contacts[$profile['url']])) {
				$data['cc'][] = $profile['url'];
				$contacts[$profile['url']] = $profile['url'];
			}

			if ($item['gravity'] != GRAVITY_PARENT) {
				continue;
			}

			$profile = APContact::getProfileByURL($parent['owner-link'], false);
			if (!empty($profile) && empty($contacts[$profile['url']])) {
				$data['cc'][] = $profile['url'];
				$contacts[$profile['url']] = $profile['url'];
			}
		}
		DBA::close($parents);

		if (empty($data['to'])) {
			$data['to'] = $data['cc'];
			$data['cc'] = [];
		}

		return $data;
	}

	/**
	 * @brief Fetches an array of inboxes for the given item and user
	 *
	 * @param array $item
	 * @param integer $uid User ID
	 *
	 * @return array with inboxes
	 */
	public static function fetchTargetInboxes($item, $uid)
	{
		$permissions = self::createPermissionBlockForItem($item);
		if (empty($permissions)) {
			return [];
		}

		$inboxes = [];

		if ($item['gravity'] == GRAVITY_ACTIVITY) {
			$item_profile = APContact::getProfileByURL($item['author-link']);
		} else {
			$item_profile = APContact::getProfileByURL($item['owner-link']);
		}

		$elements = ['to', 'cc', 'bto', 'bcc'];
		foreach ($elements as $element) {
			if (empty($permissions[$element])) {
				continue;
			}

			foreach ($permissions[$element] as $receiver) {
				if ($receiver == $item_profile['followers']) {
					$contacts = DBA::select('contact', ['notify', 'batch'], ['uid' => $uid,
						'rel' => [Contact::FOLLOWER, Contact::FRIEND], 'network' => Protocol::ACTIVITYPUB]);
					while ($contact = DBA::fetch($contacts)) {
						$contact = defaults($contact, 'batch', $contact['notify']);
						$inboxes[$contact] = $contact;
					}
					DBA::close($contacts);
				} else {
					$profile = APContact::getProfileByURL($receiver);
					if (!empty($profile)) {
						$target = defaults($profile, 'sharedinbox', $profile['inbox']);
						$inboxes[$target] = $target;
					}
				}
			}
		}

		return $inboxes;
	}

	/**
	 * @brief Returns the activity type of a given item
	 *
	 * @param array $item
	 *
	 * @return activity type
	 */
	public static function getTypeOfItem($item)
	{
		if ($item['verb'] == ACTIVITY_POST) {
			if ($item['created'] == $item['edited']) {
				$type = 'Create';
			} else {
				$type = 'Update';
			}
		} elseif ($item['verb'] == ACTIVITY_LIKE) {
			$type = 'Like';
		} elseif ($item['verb'] == ACTIVITY_DISLIKE) {
			$type = 'Dislike';
		} elseif ($item['verb'] == ACTIVITY_ATTEND) {
			$type = 'Accept';
		} elseif ($item['verb'] == ACTIVITY_ATTENDNO) {
			$type = 'Reject';
		} elseif ($item['verb'] == ACTIVITY_ATTENDMAYBE) {
			$type = 'TentativeAccept';
		} else {
			$type = '';
		}

		return $type;
	}

	/**
	 * @brief Creates an activity array for a given item id
	 *
	 * @param integer $item_id
	 * @param boolean $object_mode Is the activity item is used inside another object?
	 *
	 * @return array of activity
	 */
	public static function createActivityFromItem($item_id, $object_mode = false)
	{
		$item = Item::selectFirst([], ['id' => $item_id, 'parent-network' => Protocol::NATIVE_SUPPORT]);

		if (!DBA::isResult($item)) {
			return false;
		}

		$condition = ['item-uri' => $item['uri'], 'protocol' => Conversation::PARCEL_ACTIVITYPUB];
		$conversation = DBA::selectFirst('conversation', ['source'], $condition);
		if (DBA::isResult($conversation)) {
			$data = json_decode($conversation['source']);
			if (!empty($data)) {
				return $data;
			}
		}

		$type = self::getTypeOfItem($item);

		if (!$object_mode) {
			$data = ['@context' => self::CONTEXT];

			if ($item['deleted'] && ($item['gravity'] == GRAVITY_ACTIVITY)) {
				$type = 'Undo';
			} elseif ($item['deleted']) {
				$type = 'Delete';
			}
		} else {
			$data = [];
		}

		$data['id'] = $item['uri'] . '#' . $type;
		$data['type'] = $type;
		$data['actor'] = $item['author-link'];

		$data['published'] = DateTimeFormat::utc($item["created"]."+00:00", DateTimeFormat::ATOM);

		if ($item["created"] != $item["edited"]) {
			$data['updated'] = DateTimeFormat::utc($item["edited"]."+00:00", DateTimeFormat::ATOM);
		}

		$data['context'] = self::fetchContextURLForItem($item);

		$data = array_merge($data, ActivityPub::createPermissionBlockForItem($item));

		if (in_array($data['type'], ['Create', 'Update', 'Announce', 'Delete'])) {
			$data['object'] = self::createNote($item);
		} elseif ($data['type'] == 'Undo') {
			$data['object'] = self::createActivityFromItem($item_id, true);
		} else {
			$data['object'] = $item['thr-parent'];
		}

		$owner = User::getOwnerDataById($item['uid']);

		if (!$object_mode) {
			return LDSignature::sign($data, $owner);
		} else {
			return $data;
		}
	}

	/**
	 * @brief Creates an object array for a given item id
	 *
	 * @param integer $item_id
	 *
	 * @return object array
	 */
	public static function createObjectFromItemID($item_id)
	{
		$item = Item::selectFirst([], ['id' => $item_id, 'parent-network' => Protocol::NATIVE_SUPPORT]);

		if (!DBA::isResult($item)) {
			return false;
		}

		$data = ['@context' => self::CONTEXT];
		$data = array_merge($data, self::createNote($item));

		return $data;
	}

	/**
	 * @brief Returns a tag array for a given item array
	 *
	 * @param array $item
	 *
	 * @return array of tags
	 */
	private static function createTagList($item)
	{
		$tags = [];

		$terms = Term::tagArrayFromItemId($item['id']);
		foreach ($terms as $term) {
			if ($term['type'] == TERM_MENTION) {
				$contact = Contact::getDetailsByURL($term['url']);
				if (!empty($contact['addr'])) {
					$mention = '@' . $contact['addr'];
				} else {
					$mention = '@' . $term['url'];
				}

				$tags[] = ['type' => 'Mention', 'href' => $term['url'], 'name' => $mention];
			}
		}
		return $tags;
	}

	/**
	 * @brief Fetches the "context" value for a givem item array from the "conversation" table
	 *
	 * @param array $item
	 *
	 * @return string with context url
	 */
	private static function fetchContextURLForItem($item)
	{
		$conversation = DBA::selectFirst('conversation', ['conversation-href', 'conversation-uri'], ['item-uri' => $item['parent-uri']]);
		if (DBA::isResult($conversation) && !empty($conversation['conversation-href'])) {
			$context_uri = $conversation['conversation-href'];
		} elseif (DBA::isResult($conversation) && !empty($conversation['conversation-uri'])) {
			$context_uri = $conversation['conversation-uri'];
		} else {
			$context_uri = str_replace('/object/', '/context/', $item['parent-uri']);
		}
		return $context_uri;
	}

	/**
	 * @brief Creates a note/article object array
	 *
	 * @param array $item
	 *
	 * @return object array
	 */
	private static function createNote($item)
	{
		if (!empty($item['title'])) {
			$type = 'Article';
		} else {
			$type = 'Note';
		}

		if ($item['deleted']) {
			$type = 'Tombstone';
		}

		$data = [];
		$data['id'] = $item['uri'];
		$data['type'] = $type;

		if ($item['deleted']) {
			return $data;
		}

		$data['summary'] = null; // Ignore by now

		if ($item['uri'] != $item['thr-parent']) {
			$data['inReplyTo'] = $item['thr-parent'];
		} else {
			$data['inReplyTo'] = null;
		}

		$data['diaspora:guid'] = $item['guid'];
		$data['published'] = DateTimeFormat::utc($item["created"]."+00:00", DateTimeFormat::ATOM);

		if ($item["created"] != $item["edited"]) {
			$data['updated'] = DateTimeFormat::utc($item["edited"]."+00:00", DateTimeFormat::ATOM);
		}

		$data['url'] = $item['plink'];
		$data['attributedTo'] = $item['author-link'];
		$data['actor'] = $item['author-link'];
		$data['sensitive'] = false; // - Query NSFW
		$data['context'] = self::fetchContextURLForItem($item);

		if (!empty($item['title'])) {
			$data['name'] = BBCode::convert($item['title'], false, 7);
		}

		$data['content'] = BBCode::convert($item['body'], false, 7);
		$data['source'] = ['content' => $item['body'], 'mediaType' => "text/bbcode"];

		if (!empty($item['signed_text']) && ($item['uri'] != $item['thr-parent'])) {
			$data['diaspora:comment'] = $item['signed_text'];
		}

		$data['attachment'] = []; // @ToDo
		$data['tag'] = self::createTagList($item);
		$data = array_merge($data, ActivityPub::createPermissionBlockForItem($item));

		return $data;
	}

	/**
	 * @brief Transmits a given activity to a target
	 *
	 * @param array $activity
	 * @param string $target Target profile
	 * @param integer $uid User ID
	 */
	public static function transmitActivity($activity, $target, $uid)
	{
		$profile = APContact::getProfileByURL($target);

		$owner = User::getOwnerDataById($uid);

		$data = ['@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => System::baseUrl() . '/activity/' . System::createGUID(),
			'type' => $activity,
			'actor' => $owner['url'],
			'object' => $profile['url'],
			'to' => $profile['url']];

		logger('Sending activity ' . $activity . ' to ' . $target . ' for user ' . $uid, LOGGER_DEBUG);

		$signed = LDSignature::sign($data, $owner);
		HTTPSignature::transmit($signed, $profile['inbox'], $uid);
	}

	/**
	 * @brief Transmit a message that the contact request had been accepted
	 *
	 * @param string $target Target profile
	 * @param $id
	 * @param integer $uid User ID
	 */
	public static function transmitContactAccept($target, $id, $uid)
	{
		$profile = APContact::getProfileByURL($target);

		$owner = User::getOwnerDataById($uid);
		$data = ['@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => System::baseUrl() . '/activity/' . System::createGUID(),
			'type' => 'Accept',
			'actor' => $owner['url'],
			'object' => ['id' => $id, 'type' => 'Follow',
				'actor' => $profile['url'],
				'object' => $owner['url']],
			'to' => $profile['url']];

		logger('Sending accept to ' . $target . ' for user ' . $uid . ' with id ' . $id, LOGGER_DEBUG);

		$signed = LDSignature::sign($data, $owner);
		HTTPSignature::transmit($signed, $profile['inbox'], $uid);
	}

	/**
	 * @brief 
	 *
	 * @param string $target Target profile
	 * @param $id
	 * @param integer $uid User ID
	 */
	public static function transmitContactReject($target, $id, $uid)
	{
		$profile = APContact::getProfileByURL($target);

		$owner = User::getOwnerDataById($uid);
		$data = ['@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => System::baseUrl() . '/activity/' . System::createGUID(),
			'type' => 'Reject',
			'actor' => $owner['url'],
			'object' => ['id' => $id, 'type' => 'Follow',
				'actor' => $profile['url'],
				'object' => $owner['url']],
			'to' => $profile['url']];

		logger('Sending reject to ' . $target . ' for user ' . $uid . ' with id ' . $id, LOGGER_DEBUG);

		$signed = LDSignature::sign($data, $owner);
		HTTPSignature::transmit($signed, $profile['inbox'], $uid);
	}

	/**
	 * @brief 
	 *
	 * @param string $target Target profile
	 * @param integer $uid User ID
	 */
	public static function transmitContactUndo($target, $uid)
	{
		$profile = APContact::getProfileByURL($target);

		$id = System::baseUrl() . '/activity/' . System::createGUID();

		$owner = User::getOwnerDataById($uid);
		$data = ['@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => $id,
			'type' => 'Undo',
			'actor' => $owner['url'],
			'object' => ['id' => $id, 'type' => 'Follow',
				'actor' => $owner['url'],
				'object' => $profile['url']],
			'to' => $profile['url']];

		logger('Sending undo to ' . $target . ' for user ' . $uid . ' with id ' . $id, LOGGER_DEBUG);

		$signed = LDSignature::sign($data, $owner);
		HTTPSignature::transmit($signed, $profile['inbox'], $uid);
	}

	/**
	 * Fetches ActivityPub content from the given url
	 *
	 * @param string $url content url
	 * @return array
	 */
	public static function fetchContent($url)
	{
		$ret = Network::curl($url, false, $redirects, ['accept_content' => 'application/activity+json, application/ld+json']);
		if (!$ret['success'] || empty($ret['body'])) {
			return;
		}
		return json_decode($ret['body'], true);
	}

	/**
	 * Fetches a profile from the given url into an array that is compatible to Probe::uri
	 *
	 * @param string $url profile url
	 * @return array
	 */
	public static function probeProfile($url)
	{
		$apcontact = APContact::getProfileByURL($url, true);
		if (empty($apcontact)) {
			return false;
		}

		$profile = ['network' => Protocol::ACTIVITYPUB];
		$profile['nick'] = $apcontact['nick'];
		$profile['name'] = $apcontact['name'];
		$profile['guid'] = $apcontact['uuid'];
		$profile['url'] = $apcontact['url'];
		$profile['addr'] = $apcontact['addr'];
		$profile['alias'] = $apcontact['alias'];
		$profile['photo'] = $apcontact['photo'];
		// $profile['community']
		// $profile['keywords']
		// $profile['location']
		$profile['about'] = $apcontact['about'];
		$profile['batch'] = $apcontact['sharedinbox'];
		$profile['notify'] = $apcontact['inbox'];
		$profile['poll'] = $apcontact['outbox'];
		$profile['pubkey'] = $apcontact['pubkey'];
		$profile['baseurl'] = $apcontact['baseurl'];

		// Remove all "null" fields
		foreach ($profile as $field => $content) {
			if (is_null($content)) {
				unset($profile[$field]);
			}
		}

		return $profile;
	}

	/**
	 * @brief 
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
	 * @brief 
	 *
	 * @param $url
	 * @param integer $uid User ID
	 */
	public static function fetchOutbox($url, $uid)
	{
		$data = self::fetchContent($url);
		if (empty($data)) {
			return;
		}

		if (!empty($data['orderedItems'])) {
			$items = $data['orderedItems'];
		} elseif (!empty($data['first']['orderedItems'])) {
			$items = $data['first']['orderedItems'];
		} elseif (!empty($data['first'])) {
			self::fetchOutbox($data['first'], $uid);
			return;
		} else {
			$items = [];
		}

		foreach ($items as $activity) {
			self::processActivity($activity, '', $uid, true);
		}
	}

	/**
	 * @brief 
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
			$object_data = self::ProcessObject($activity);
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
	 * @brief 
	 *
	 * @param array $activity
	 * @param $body
	 * @param integer $uid User ID
	 * @param $trust_source
	 */
	private static function processActivity($activity, $body = '', $uid = null, $trust_source = false)
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
		}

		switch ($activity['type']) {
			case 'Create':
			case 'Announce':
				self::createItem($object_data, $body);
				break;

			case 'Like':
				self::likeItem($object_data, $body);
				break;

			case 'Dislike':
				self::dislikeItem($object_data, $body);
				break;

			case 'Update':
				if (in_array($object_data['object_type'], ['Person', 'Organization', 'Service', 'Group', 'Application'])) {
					self::updatePerson($object_data, $body);
				}
				break;

			case 'Delete':
				break;

			case 'Follow':
				self::followUser($object_data);
				break;

			case 'Accept':
				if ($object_data['object_type'] == 'Follow') {
					self::acceptFollowUser($object_data);
				}
				break;

			case 'Undo':
				if ($object_data['object_type'] == 'Follow') {
					self::undoFollowUser($object_data);
				} elseif (in_array($object_data['object_type'], ['Like', 'Dislike', 'Accept', 'Reject', 'TentativeAccept'])) {
					self::undoActivity($object_data);
				}
				break;

			default:
				logger('Unknown activity: ' . $activity['type'], LOGGER_DEBUG);
				break;
		}
	}

	/**
	 * @brief 
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
			$profile = APContact::getProfileByURL($actor);
			$followers = defaults($profile, 'followers', '');

			logger('Actor: ' . $actor . ' - Followers: ' . $followers, LOGGER_DEBUG);
		} else {
			logger('Empty actor', LOGGER_DEBUG);
			$followers = '';
		}

		$elements = ['to', 'cc', 'bto', 'bcc'];
		foreach ($elements as $element) {
			if (empty($activity[$element])) {
				continue;
			}

			// The receiver can be an arror or a string
			if (is_string($activity[$element])) {
				$activity[$element] = [$activity[$element]];
			}

			foreach ($activity[$element] as $receiver) {
				if ($receiver == self::PUBLIC) {
					$receivers['uid:0'] = 0;
				}

				if (($receiver == self::PUBLIC) && !empty($actor)) {
					// This will most likely catch all OStatus connections to Mastodon
					$condition = ['alias' => [$actor, normalise_link($actor)], 'rel' => [Contact::SHARING, Contact::FRIEND]];
					$contacts = DBA::select('contact', ['uid'], $condition);
					while ($contact = DBA::fetch($contacts)) {
						if ($contact['uid'] != 0) {
							$receivers['uid:' . $contact['uid']] = $contact['uid'];
						}
					}
					DBA::close($contacts);
				}

				if (in_array($receiver, [$followers, self::PUBLIC]) && !empty($actor)) {
					$condition = ['nurl' => normalise_link($actor), 'rel' => [Contact::SHARING, Contact::FRIEND],
						'network' => Protocol::ACTIVITYPUB];
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
	 * @brief 
	 *
	 * @param $cid
	 * @param integer $uid User ID
	 * @param $url
	 */
	private static function switchContact($cid, $uid, $url)
	{
		$profile = ActivityPub::probeProfile($url);
		if (empty($profile)) {
			return;
		}

		logger('Switch contact ' . $cid . ' (' . $profile['url'] . ') for user ' . $uid . ' from OStatus to ActivityPub');

		$photo = $profile['photo'];
		unset($profile['photo']);
		unset($profile['baseurl']);

		$profile['nurl'] = normalise_link($profile['url']);
		DBA::update('contact', $profile, ['id' => $cid]);

		Contact::updateAvatar($photo, $uid, $cid);
	}

	/**
	 * @brief 
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
	 * @brief 
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
	 * @brief 
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
			$data = self::fetchContent($object_id);
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
			$data = self::createNote($item);
		}

		if (empty($data['type'])) {
			logger('Empty type', LOGGER_DEBUG);
			return false;
		}

		switch ($data['type']) {
			case 'Note':
			case 'Article':
			case 'Video':
				return self::ProcessObject($data);

			case 'Announce':
				if (empty($data['object'])) {
					return false;
				}
				return self::fetchObject($data['object']);

			case 'Person':
			case 'Tombstone':
				break;

			default:
				logger('Unknown object type: ' . $data['type'], LOGGER_DEBUG);
				break;
		}
	}

	/**
	 * @brief 
	 *
	 * @param $object
	 *
	 * @return 
	 */
	private static function ProcessObject(&$object)
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

	/**
	 * @brief Converts mentions from Pleroma into the Friendica format
	 *
	 * @param string $body
	 *
	 * @return converted body
	 */
	private static function convertMentions($body)
	{
		$URLSearchString = "^\[\]";
		$body = preg_replace("/\[url\=([$URLSearchString]*)\]([#@!])(.*?)\[\/url\]/ism", '$2[url=$1]$3[/url]', $body);

		return $body;
	}

	/**
	 * @brief Constructs a string with tags for a given tag array
	 *
	 * @param array $tags
	 * @param boolean $sensitive
	 *
	 * @return string with tags
	 */
	private static function constructTagList($tags, $sensitive)
	{
		if (empty($tags)) {
			return '';
		}

		$tag_text = '';
		foreach ($tags as $tag) {
			if (in_array($tag['type'], ['Mention', 'Hashtag'])) {
				if (!empty($tag_text)) {
					$tag_text .= ',';
				}

				$tag_text .= substr($tag['name'], 0, 1) . '[url=' . $tag['href'] . ']' . substr($tag['name'], 1) . '[/url]';
			}
		}

		/// @todo add nsfw for $sensitive

		return $tag_text;
	}

	/**
	 * @brief 
	 *
	 * @param $attachments
	 * @param array $item
	 *
	 * @return item array
	 */
	private static function constructAttachList($attachments, $item)
	{
		if (empty($attachments)) {
			return $item;
		}

		foreach ($attachments as $attach) {
			$filetype = strtolower(substr($attach['mediaType'], 0, strpos($attach['mediaType'], '/')));
			if ($filetype == 'image') {
				$item['body'] .= "\n[img]".$attach['url'].'[/img]';
			} else {
				if (!empty($item["attach"])) {
					$item["attach"] .= ',';
				} else {
					$item["attach"] = '';
				}
				if (!isset($attach['length'])) {
					$attach['length'] = "0";
				}
				$item["attach"] .= '[attach]href="'.$attach['url'].'" length="'.$attach['length'].'" type="'.$attach['mediaType'].'" title="'.defaults($attach, 'name', '').'"[/attach]';
			}
		}

		return $item;
	}

	/**
	 * @brief 
	 *
	 * @param array $activity
	 * @param $body
	 */
	private static function createItem($activity, $body)
	{
		$item = [];
		$item['verb'] = ACTIVITY_POST;
		$item['parent-uri'] = $activity['reply-to-id'];

		if ($activity['reply-to-id'] == $activity['id']) {
			$item['gravity'] = GRAVITY_PARENT;
			$item['object-type'] = ACTIVITY_OBJ_NOTE;
		} else {
			$item['gravity'] = GRAVITY_COMMENT;
			$item['object-type'] = ACTIVITY_OBJ_COMMENT;
		}

		if (($activity['id'] != $activity['reply-to-id']) && !Item::exists(['uri' => $activity['reply-to-id']])) {
			logger('Parent ' . $activity['reply-to-id'] . ' not found. Try to refetch it.');
			self::fetchMissingActivity($activity['reply-to-id'], $activity);
		}

		self::postItem($activity, $item, $body);
	}

	/**
	 * @brief 
	 *
	 * @param array $activity
	 * @param $body
	 */
	private static function likeItem($activity, $body)
	{
		$item = [];
		$item['verb'] = ACTIVITY_LIKE;
		$item['parent-uri'] = $activity['object'];
		$item['gravity'] = GRAVITY_ACTIVITY;
		$item['object-type'] = ACTIVITY_OBJ_NOTE;

		self::postItem($activity, $item, $body);
	}

	/**
	 * @brief 
	 *
	 * @param array $activity
	 * @param $body
	 */
	private static function dislikeItem($activity, $body)
	{
		$item = [];
		$item['verb'] = ACTIVITY_DISLIKE;
		$item['parent-uri'] = $activity['object'];
		$item['gravity'] = GRAVITY_ACTIVITY;
		$item['object-type'] = ACTIVITY_OBJ_NOTE;

		self::postItem($activity, $item, $body);
	}

	/**
	 * @brief 
	 *
	 * @param array $activity
	 * @param array $item
	 * @param $body
	 */
	private static function postItem($activity, $item, $body)
	{
		/// @todo What to do with $activity['context']?

		if (($item['gravity'] != GRAVITY_PARENT) && !Item::exists(['uri' => $item['parent-uri']])) {
			logger('Parent ' . $item['parent-uri'] . ' not found, message will be discarded.', LOGGER_DEBUG);
			return;
		}

		$item['network'] = Protocol::ACTIVITYPUB;
		$item['private'] = !in_array(0, $activity['receiver']);
		$item['author-id'] = Contact::getIdForURL($activity['author'], 0, true);
		$item['owner-id'] = Contact::getIdForURL($activity['owner'], 0, true);
		$item['uri'] = $activity['id'];
		$item['created'] = $activity['published'];
		$item['edited'] = $activity['updated'];
		$item['guid'] = $activity['diaspora:guid'];
		$item['title'] = HTML::toBBCode($activity['name']);
		$item['content-warning'] = HTML::toBBCode($activity['summary']);
		$item['body'] = self::convertMentions(HTML::toBBCode($activity['content']));
		$item['location'] = $activity['location'];
		$item['tag'] = self::constructTagList($activity['tags'], $activity['sensitive']);
		$item['app'] = $activity['service'];
		$item['plink'] = defaults($activity, 'alternate-url', $item['uri']);

		$item = self::constructAttachList($activity['attachments'], $item);

		$source = JsonLD::fetchElement($activity, 'source', 'content', 'mediaType', 'text/bbcode');
		if (!empty($source)) {
			$item['body'] = $source;
		}

		$item['protocol'] = Conversation::PARCEL_ACTIVITYPUB;
		$item['source'] = $body;
		$item['conversation-href'] = $activity['context'];
		$item['conversation-uri'] = $activity['conversation'];

		foreach ($activity['receiver'] as $receiver) {
			$item['uid'] = $receiver;
			$item['contact-id'] = Contact::getIdForURL($activity['author'], $receiver, true);

			if (($receiver != 0) && empty($item['contact-id'])) {
				$item['contact-id'] = Contact::getIdForURL($activity['author'], 0, true);
			}

			$item_id = Item::insert($item);
			logger('Storing for user ' . $item['uid'] . ': ' . $item_id);
		}
	}

	/**
	 * @brief 
	 *
	 * @param $url
	 * @param $child
	 */
	private static function fetchMissingActivity($url, $child)
	{
		if (Config::get('system', 'ostatus_full_threads')) {
			return;
		}

		$object = ActivityPub::fetchContent($url);
		if (empty($object)) {
			logger('Activity ' . $url . ' was not fetchable, aborting.');
			return;
		}

		$activity = [];
		$activity['@context'] = $object['@context'];
		unset($object['@context']);
		$activity['id'] = $object['id'];
		$activity['to'] = defaults($object, 'to', []);
		$activity['cc'] = defaults($object, 'cc', []);
		$activity['actor'] = $child['author'];
		$activity['object'] = $object;
		$activity['published'] = $object['published'];
		$activity['type'] = 'Create';

		self::processActivity($activity);
		logger('Activity ' . $url . ' had been fetched and processed.');
	}

	/**
	 * @brief Returns the user id of a given profile url
	 *
	 * @param string $profile
	 *
	 * @return integer user id
	 */
	private static function getUserOfProfile($profile)
	{
		$self = DBA::selectFirst('contact', ['uid'], ['nurl' => normalise_link($profile), 'self' => true]);
		if (!DBA::isResult($self)) {
			return false;
		} else {
			return $self['uid'];
		}
	}

	/**
	 * @brief perform a "follow" request
	 *
	 * @param array $activity
	 */
	private static function followUser($activity)
	{
		$actor = JsonLD::fetchElement($activity, 'object', 'id');
		$uid = self::getUserOfProfile($actor);
		if (empty($uid)) {
			return;
		}

		$owner = User::getOwnerDataById($uid);

		$cid = Contact::getIdForURL($activity['owner'], $uid);
		if (!empty($cid)) {
			$contact = DBA::selectFirst('contact', [], ['id' => $cid]);
		} else {
			$contact = false;
		}

		$item = ['author-id' => Contact::getIdForURL($activity['owner']),
			'author-link' => $activity['owner']];

		Contact::addRelationship($owner, $contact, $item);
		$cid = Contact::getIdForURL($activity['owner'], $uid);
		if (empty($cid)) {
			return;
		}

		$contact = DBA::selectFirst('contact', ['network'], ['id' => $cid]);
		if ($contact['network'] != Protocol::ACTIVITYPUB) {
			Contact::updateFromProbe($cid, Protocol::ACTIVITYPUB);
		}

		DBA::update('contact', ['hub-verify' => $activity['id']], ['id' => $cid]);
		logger('Follow user ' . $uid . ' from contact ' . $cid . ' with id ' . $activity['id']);
	}

	/**
	 * @brief Update the given profile
	 *
	 * @param array $activity
	 */
	private static function updatePerson($activity)
	{
		if (empty($activity['object']['id'])) {
			return;
		}

		logger('Updating profile for ' . $activity['object']['id'], LOGGER_DEBUG);
		APContact::getProfileByURL($activity['object']['id'], true);
	}

	/**
	 * @brief Accept a follow request
	 *
	 * @param array $activity
	 */
	private static function acceptFollowUser($activity)
	{
		$actor = JsonLD::fetchElement($activity, 'object', 'actor');
		$uid = self::getUserOfProfile($actor);
		if (empty($uid)) {
			return;
		}

		$owner = User::getOwnerDataById($uid);

		$cid = Contact::getIdForURL($activity['owner'], $uid);
		if (empty($cid)) {
			logger('No contact found for ' . $activity['owner'], LOGGER_DEBUG);
			return;
		}

		$fields = ['pending' => false];

		$contact = DBA::selectFirst('contact', ['rel'], ['id' => $cid]);
		if ($contact['rel'] == Contact::FOLLOWER) {
			$fields['rel'] = Contact::FRIEND;
		}

		$condition = ['id' => $cid];
		DBA::update('contact', $fields, $condition);
		logger('Accept contact request from contact ' . $cid . ' for user ' . $uid, LOGGER_DEBUG);
	}

	/**
	 * @brief Undo activity like "like" or "dislike"
	 *
	 * @param array $activity
	 */
	private static function undoActivity($activity)
	{
		$activity_url = JsonLD::fetchElement($activity, 'object', 'id');
		if (empty($activity_url)) {
			return;
		}

		$actor = JsonLD::fetchElement($activity, 'object', 'actor');
		if (empty($actor)) {
			return;
		}

		$author_id = Contact::getIdForURL($actor);
		if (empty($author_id)) {
			return;
		}

		Item::delete(['uri' => $activity_url, 'author-id' => $author_id, 'gravity' => GRAVITY_ACTIVITY]);
	}

	/**
	 * @brief Activity to remove a follower
	 *
	 * @param array $activity
	 */
	private static function undoFollowUser($activity)
	{
		$object = JsonLD::fetchElement($activity, 'object', 'object');
		$uid = self::getUserOfProfile($object);
		if (empty($uid)) {
			return;
		}

		$owner = User::getOwnerDataById($uid);

		$cid = Contact::getIdForURL($activity['owner'], $uid);
		if (empty($cid)) {
			logger('No contact found for ' . $activity['owner'], LOGGER_DEBUG);
			return;
		}

		$contact = DBA::selectFirst('contact', [], ['id' => $cid]);
		if (!DBA::isResult($contact)) {
			return;
		}

		Contact::removeFollower($owner, $contact);
		logger('Undo following request from contact ' . $cid . ' for user ' . $uid, LOGGER_DEBUG);
	}
}
