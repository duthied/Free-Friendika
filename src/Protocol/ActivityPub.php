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
use Friendica\Model\Item;
use Friendica\Model\Term;
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Crypto;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Util\JsonLD;
use Friendica\Util\LDSignature;

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
 * https://github.com/digitalbazaar/php-json-ld
 *
 * Part of the code for HTTP signing is taken from the Osada project.
 * https://framagit.org/macgirvin/osada
 *
 * To-do:
 *
 * Receiver:
 * - Activities: Dislike, Update, Delete
 * - Object Types: Person, Tombstome
 *
 * Transmitter:
 * - Activities: Like, Dislike, Update, Delete, Announce
 * - Object Tyoes: Article, Person, Tombstone
 *
 * General:
 * - Endpoints: Outbox, Follower, Following
 * - General cleanup
 * - Queueing unsucessful deliveries
 */
class ActivityPub
{
	const PUBLIC = 'https://www.w3.org/ns/activitystreams#Public';

	public static function isRequest()
	{
		return stristr(defaults($_SERVER, 'HTTP_ACCEPT', ''), 'application/activity+json') ||
			stristr(defaults($_SERVER, 'HTTP_ACCEPT', ''), 'application/ld+json');
	}

	/**
	 * Return the ActivityPub profile of the given user
	 *
	 * @param integer $uid User ID
	 * @return array
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

		$data = ['@context' => ['https://www.w3.org/ns/activitystreams', 'https://w3id.org/security/v1',
			['vcard' => 'http://www.w3.org/2006/vcard/ns#', 'uuid' => 'http://schema.org/identifier',
			'sensitive' => 'as:sensitive', 'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers']]];

		$data['id'] = $contact['url'];
		$data['uuid'] = $user['guid'];
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
		$profile = ActivityPub::fetchprofile($actor);

		$item_profile = ActivityPub::fetchprofile($item['owner-link']);

		$permissions = [];

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
				if ($receiver != $item['owner-link']) {
					$permissions[$element][] = $receiver;
				}
			}
		}
		return $permissions;
	}

	public static function createPermissionBlockForItem($item)
	{
		$data = ['to' => [], 'cc' => []];

		$data = array_merge($data, self::fetchPermissionBlockFromConversation($item));

		$actor_profile = ActivityPub::fetchprofile($item['author-link']);

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
				$profile = self::fetchprofile($term['url']);
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

		$parents = Item::select(['author-link', 'owner-link'], ['parent' => $item['parent']]);
		while ($parent = Item::fetch($parents)) {
			$profile = self::fetchprofile($parent['author-link']);
			if (!empty($profile) && empty($contacts[$profile['url']])) {
				$data['cc'][] = $profile['url'];
				$contacts[$profile['url']] = $profile['url'];
			}

			$profile = self::fetchprofile($parent['owner-link']);
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

	public static function fetchTargetInboxes($item, $uid)
	{
		$permissions = self::createPermissionBlockForItem($item);
		if (empty($permissions)) {
			return [];
		}

		$inboxes = [];

		$item_profile = ActivityPub::fetchprofile($item['owner-link']);

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
					$profile = self::fetchprofile($receiver);
					if (!empty($profile)) {
						$target = defaults($profile, 'sharedinbox', $profile['inbox']);
						$inboxes[$target] = $target;
					}
				}
			}
		}

		if (!empty($item_profile['sharedinbox'])) {
			unset($inboxes[$item_profile['sharedinbox']]);
		}

		if (!empty($item_profile['inbox'])) {
			unset($inboxes[$item_profile['inbox']]);
		}

		return $inboxes;
	}

	public static function createActivityFromItem($item_id)
	{
		$item = Item::selectFirst([], ['id' => $item_id]);

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

		$data = ['@context' => ['https://www.w3.org/ns/activitystreams', 'https://w3id.org/security/v1',
			['ostatus' => 'http://ostatus.org#', 'sensitive' => 'as:sensitive',
			'Hashtag' => 'as:Hashtag', 'atomUri' => 'ostatus:atomUri',
			'conversation' => 'ostatus:conversation',
			'inReplyToAtomUri' => 'ostatus:inReplyToAtomUri']]];

		$data['id'] = $item['uri'] . '#activity';
		$data['type'] = 'Create';
		$data['actor'] = $item['author-link'];

		$data['published'] = DateTimeFormat::utc($item["created"]."+00:00", DateTimeFormat::ATOM);

		if ($item["created"] != $item["edited"]) {
			$data['updated'] = DateTimeFormat::utc($item["edited"]."+00:00", DateTimeFormat::ATOM);
		}

		$data['context_id'] = $item['parent'];
		$data['context'] = self::createConversationURLFromItem($item);

		$data = array_merge($data, ActivityPub::createPermissionBlockForItem($item));

		$data['object'] = self::createObjectTypeFromItem($item);

		$owner = User::getOwnerDataById($item['uid']);

		return LDSignature::sign($data, $owner);
	}

	public static function createObjectFromItemID($item_id)
	{
		$item = Item::selectFirst([], ['id' => $item_id]);

		if (!DBA::isResult($item)) {
			return false;
		}

		$data = ['@context' => ['https://www.w3.org/ns/activitystreams', 'https://w3id.org/security/v1',
			['ostatus' => 'http://ostatus.org#', 'sensitive' => 'as:sensitive',
			'Hashtag' => 'as:Hashtag', 'atomUri' => 'ostatus:atomUri',
			'conversation' => 'ostatus:conversation',
			'inReplyToAtomUri' => 'ostatus:inReplyToAtomUri']]];

		$data = array_merge($data, self::createObjectTypeFromItem($item));


		return $data;
	}

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

	private static function createConversationURLFromItem($item)
	{
		$conversation = DBA::selectFirst('conversation', ['conversation-uri'], ['item-uri' => $item['parent-uri']]);
		if (DBA::isResult($conversation) && !empty($conversation['conversation-uri'])) {
			$conversation_uri = $conversation['conversation-uri'];
		} else {
			$conversation_uri = $item['parent-uri'];
		}
		return $conversation_uri;
	}

	private static function createObjectTypeFromItem($item)
	{
		if (!empty($item['title'])) {
			$type = 'Article';
		} else {
			$type = 'Note';
		}

		$data = [];
		$data['id'] = $item['uri'];
		$data['type'] = $type;
		$data['summary'] = null; // Ignore by now

		if ($item['uri'] != $item['thr-parent']) {
			$data['inReplyTo'] = $item['thr-parent'];
		} else {
			$data['inReplyTo'] = null;
		}

		$data['published'] = DateTimeFormat::utc($item["created"]."+00:00", DateTimeFormat::ATOM);

		if ($item["created"] != $item["edited"]) {
			$data['updated'] = DateTimeFormat::utc($item["edited"]."+00:00", DateTimeFormat::ATOM);
		}

		$data['url'] = $item['plink'];
		$data['attributedTo'] = $item['author-link'];
		$data['actor'] = $item['author-link'];
		$data['sensitive'] = false; // - Query NSFW
		$data['context_id'] = $item['parent'];
		$data['conversation'] = $data['context'] = self::createConversationURLFromItem($item);

		if (!empty($item['title'])) {
			$data['name'] = BBCode::convert($item['title'], false, 7);
		}

		$data['content'] = BBCode::convert($item['body'], false, 7);
		$data['source'] = ['content' => $item['body'], 'mediaType' => "text/bbcode"];
		$data['attachment'] = []; // @ToDo
		$data['tag'] = self::createTagList($item);
		$data = array_merge($data, ActivityPub::createPermissionBlockForItem($item));

		//$data['emoji'] = []; // Ignore by now
		return $data;
	}

	public static function transmitActivity($activity, $target, $uid)
	{
		$profile = self::fetchprofile($target);

		$owner = User::getOwnerDataById($uid);

		$data = ['@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => System::baseUrl() . '/activity/' . System::createGUID(),
			'type' => $activity,
			'actor' => $owner['url'],
			'object' => $profile['url'],
			'to' => $profile['url']];

		logger('Sending activity ' . $activity . ' to ' . $target . ' for user ' . $uid, LOGGER_DEBUG);

		$signed = LDSignature::sign($data, $owner);
		return HTTPSignature::transmit($signed, $profile['inbox'], $uid);
	}

	public static function transmitContactAccept($target, $id, $uid)
	{
		$profile = self::fetchprofile($target);

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
		return HTTPSignature::transmit($signed, $profile['inbox'], $uid);
	}

	public static function transmitContactReject($target, $id, $uid)
	{
		$profile = self::fetchprofile($target);

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
		return HTTPSignature::transmit($signed, $profile['inbox'], $uid);
	}

	public static function transmitContactUndo($target, $uid)
	{
		$profile = self::fetchprofile($target);

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
		return HTTPSignature::transmit($signed, $profile['inbox'], $uid);
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
	 * Resolves the profile url from the address by using webfinger
	 *
	 * @param string $addr profile address (user@domain.tld)
	 * @return string url
	 */
	private static function addrToUrl($addr)
	{
		$addr_parts = explode('@', $addr);
		if (count($addr_parts) != 2) {
			return false;
		}

		$webfinger = 'https://' . $addr_parts[1] . '/.well-known/webfinger?resource=acct:' . urlencode($addr);

		$ret = Network::curl($webfinger, false, $redirects, ['accept_content' => 'application/jrd+json,application/json']);
		if (!$ret['success'] || empty($ret['body'])) {
			return false;
		}

		$data = json_decode($ret['body'], true);

		if (empty($data['links'])) {
			return false;
		}

		foreach ($data['links'] as $link) {
			if (empty($link['href']) || empty($link['rel']) || empty($link['type'])) {
				continue;
			}

			if (($link['rel'] == 'self') && ($link['type'] == 'application/activity+json')) {
				return $link['href'];
			}
		}

		return false;
	}

	public static function fetchprofile($url, $update = false)
	{
		if (empty($url)) {
			return false;
		}

		if (!$update) {
			$apcontact = DBA::selectFirst('apcontact', [], ['url' => $url]);
			if (DBA::isResult($apcontact)) {
				return $apcontact;
			}

			$apcontact = DBA::selectFirst('apcontact', [], ['alias' => $url]);
			if (DBA::isResult($apcontact)) {
				return $apcontact;
			}

			$apcontact = DBA::selectFirst('apcontact', [], ['addr' => $url]);
			if (DBA::isResult($apcontact)) {
				return $apcontact;
			}
		}

		if (empty(parse_url($url, PHP_URL_SCHEME))) {
			$url = self::addrToUrl($url);
			if (empty($url)) {
				return false;
			}
		}

		$data = self::fetchContent($url);

		if (empty($data) || empty($data['id']) || empty($data['inbox'])) {
			return false;
		}

		$apcontact = [];
		$apcontact['url'] = $data['id'];
		$apcontact['uuid'] = defaults($data, 'uuid', null);
		$apcontact['type'] = defaults($data, 'type', null);
		$apcontact['following'] = defaults($data, 'following', null);
		$apcontact['followers'] = defaults($data, 'followers', null);
		$apcontact['inbox'] = defaults($data, 'inbox', null);
		$apcontact['outbox'] = defaults($data, 'outbox', null);
		$apcontact['sharedinbox'] = JsonLD::fetchElement($data, 'endpoints', 'sharedInbox');
		$apcontact['nick'] = defaults($data, 'preferredUsername', null);
		$apcontact['name'] = defaults($data, 'name', $apcontact['nick']);
		$apcontact['about'] = defaults($data, 'summary', '');
		$apcontact['photo'] = JsonLD::fetchElement($data, 'icon', 'url');
		$apcontact['alias'] = JsonLD::fetchElement($data, 'url', 'href');

		$parts = parse_url($apcontact['url']);
		unset($parts['scheme']);
		unset($parts['path']);
		$apcontact['addr'] = $apcontact['nick'] . '@' . str_replace('//', '', Network::unparseURL($parts));

		$apcontact['pubkey'] = trim(JsonLD::fetchElement($data, 'publicKey', 'publicKeyPem'));

		// To-Do
		// manuallyApprovesFollowers

		// Unhandled
		// @context, tag, attachment, image, nomadicLocations, signature, following, followers, featured, movedTo, liked

		// Unhandled from Misskey
		// sharedInbox, isCat

		// Unhandled from Kroeg
		// kroeg:blocks, updated

		// Check if the address is resolvable
		if (self::addrToUrl($apcontact['addr']) == $apcontact['url']) {
			$parts = parse_url($apcontact['url']);
			unset($parts['path']);
			$apcontact['baseurl'] = Network::unparseURL($parts);
		} else {
			$apcontact['addr'] = null;
		}

		if ($apcontact['url'] == $apcontact['alias']) {
			$apcontact['alias'] = null;
		}

		$apcontact['updated'] = DateTimeFormat::utcNow();

		DBA::update('apcontact', $apcontact, ['url' => $url], true);

		return $apcontact;
	}

	/**
	 * Fetches a profile from the given url into an array that is compatible to Probe::uri
	 *
	 * @param string $url profile url
	 * @return array
	 */
	public static function probeProfile($url)
	{
		$apcontact = self::fetchprofile($url, true);
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

	private static function prepareObjectData($activity, $uid, $trust_source)
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

		if (is_string($activity['object'])) {
			$object_url = $activity['object'];
		} elseif (!empty($activity['object']['id'])) {
			$object_url = $activity['object']['id'];
		} else {
			logger('No object found', LOGGER_DEBUG);
			return [];
		}

		// Fetch the content only on activities where this matters
		if (in_array($activity['type'], ['Create', 'Update', 'Announce'])) {
			$object_data = self::fetchObject($object_url, $activity['object'], $trust_source);
			if (empty($object_data)) {
				logger("Object data couldn't be processed", LOGGER_DEBUG);
				return [];
			}
		} elseif ($activity['type'] == 'Accept') {
			$object_data = [];
			$object_data['object_type'] = JsonLD::fetchElement($activity, 'object', 'type');
			$object_data['object'] = JsonLD::fetchElement($activity, 'object', 'actor');
		} elseif ($activity['type'] == 'Undo') {
			$object_data = [];
			$object_data['object_type'] = JsonLD::fetchElement($activity, 'object', 'type');
			$object_data['object'] = JsonLD::fetchElement($activity, 'object', 'object');
		} elseif (in_array($activity['type'], ['Like', 'Dislike'])) {
			// Create a mostly empty array out of the activity data (instead of the object).
			// This way we later don't have to check for the existence of ech individual array element.
			$object_data = self::processCommonData($activity);
			$object_data['name'] = $activity['type'];
			$object_data['author'] = $activity['actor'];
			$object_data['object'] = $object_url;
		} elseif ($activity['type'] == 'Follow') {
			$object_data['id'] = $activity['id'];
			$object_data['object'] = $object_url;
		} else {
			$object_data = [];
		}

		$object_data = self::addActivityFields($object_data, $activity);

		$object_data['type'] = $activity['type'];
		$object_data['owner'] = $actor;
		$object_data['receiver'] = array_merge(defaults($object_data, 'receiver', []), $receivers);

		return $object_data;
	}

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

		// Non standard
		// title, atomUri, context_id, statusnetConversationId

		// To-Do?
		// context, location, signature;

		logger('Processing activity: ' . $activity['type'], LOGGER_DEBUG);

		$object_data = self::prepareObjectData($activity, $uid, $trust_source);
		if (empty($object_data)) {
			logger('No object data found', LOGGER_DEBUG);
			return;
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
				break;

			case 'Update':
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
				}
				break;

			default:
				logger('Unknown activity: ' . $activity['type'], LOGGER_DEBUG);
				break;
		}
	}

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
			$profile = self::fetchprofile($actor);
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
		return $receivers;
	}

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

	private static function fetchObject($object_url, $object = [], $trust_source = false)
	{
		if (!$trust_source || is_string($object)) {
			$data = self::fetchContent($object_url);
			if (empty($data)) {
				logger('Empty content for ' . $object_url . ', check if content is available locally.', LOGGER_DEBUG);
				$data = $object_url;
			} else {
				logger('Fetched content for ' . $object_url, LOGGER_DEBUG);
			}
		} else {
			logger('Using original object for url ' . $object_url, LOGGER_DEBUG);
			$data = $object;
		}

		if (is_string($data)) {
			$item = Item::selectFirst([], ['uri' => $data]);
			if (!DBA::isResult($item)) {
				logger('Object with url ' . $data . ' was not found locally.', LOGGER_DEBUG);
				return false;
			}
			logger('Using already stored item for url ' . $object_url, LOGGER_DEBUG);
			$data = self::createObjectTypeFromItem($item);
		}

		if (empty($data['type'])) {
			logger('Empty type', LOGGER_DEBUG);
			return false;
		} else {
			$type = $data['type'];
			logger('Type ' . $type, LOGGER_DEBUG);
		}

		if (in_array($type, ['Note', 'Article', 'Video'])) {
			$common = self::processCommonData($data);
		}

		switch ($type) {
			case 'Note':
				return array_merge($common, self::processNote($data));
			case 'Article':
				return array_merge($common, self::processArticle($data));
			case 'Video':
				return array_merge($common, self::processVideo($data));

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

	private static function processCommonData(&$object)
	{
		if (empty($object['id'])) {
			return false;
		}

		$object_data = [];
		$object_data['type'] = $object['type'];
		$object_data['uri'] = $object['id'];

		if (!empty($object['inReplyTo'])) {
			$object_data['reply-to-uri'] = JsonLD::fetchElement($object, 'inReplyTo', 'id');
		} else {
			$object_data['reply-to-uri'] = $object_data['uri'];
		}

		$object_data['published'] = defaults($object, 'published', null);
		$object_data['updated'] = defaults($object, 'updated', $object_data['published']);

		if (empty($object_data['published']) && !empty($object_data['updated'])) {
			$object_data['published'] = $object_data['updated'];
		}

		$object_data['uuid'] = defaults($object, 'uuid', null);
		$object_data['owner'] = $object_data['author'] = JsonLD::fetchElement($object, 'attributedTo', 'id');
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

		// Unhandled
		// @context, type, actor, signature, mediaType, duration, replies, icon

		// Also missing: (Defined in the standard, but currently unused)
		// audience, preview, endTime, startTime, generator, image

		return $object_data;
	}

	private static function processNote($object)
	{
		$object_data = [];

		// To-Do?
		// emoji, atomUri, inReplyToAtomUri

		// Unhandled
		// contentMap, announcement_count, announcements, context_id, likes, like_count
		// inReplyToStatusId, shares, quoteUrl, statusnetConversationId

		return $object_data;
	}

	private static function processArticle($object)
	{
		$object_data = [];

		return $object_data;
	}

	private static function processVideo($object)
	{
		$object_data = [];

		// To-Do?
		// category, licence, language, commentsEnabled

		// Unhandled
		// views, waitTranscoding, state, support, subtitleLanguage
		// likes, dislikes, shares, comments

		return $object_data;
	}

	private static function convertMentions($body)
	{
		$URLSearchString = "^\[\]";
		$body = preg_replace("/\[url\=([$URLSearchString]*)\]([#@!])(.*?)\[\/url\]/ism", '$2[url=$1]$3[/url]', $body);

		return $body;
	}

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

				if (empty($tag['href'])) {
					//$tag['href']
					logger('Blubb!');
				}

				$tag_text .= substr($tag['name'], 0, 1) . '[url=' . $tag['href'] . ']' . substr($tag['name'], 1) . '[/url]';
			}
		}

		/// @todo add nsfw for $sensitive

		return $tag_text;
	}

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

	private static function createItem($activity, $body)
	{
		$item = [];
		$item['verb'] = ACTIVITY_POST;
		$item['parent-uri'] = $activity['reply-to-uri'];

		if ($activity['reply-to-uri'] == $activity['uri']) {
			$item['gravity'] = GRAVITY_PARENT;
			$item['object-type'] = ACTIVITY_OBJ_NOTE;
		} else {
			$item['gravity'] = GRAVITY_COMMENT;
			$item['object-type'] = ACTIVITY_OBJ_COMMENT;
		}

		if (($activity['uri'] != $activity['reply-to-uri']) && !Item::exists(['uri' => $activity['reply-to-uri']])) {
			logger('Parent ' . $activity['reply-to-uri'] . ' not found. Try to refetch it.');
			self::fetchMissingActivity($activity['reply-to-uri'], $activity);
		}

		self::postItem($activity, $item, $body);
	}

	private static function likeItem($activity, $body)
	{
		$item = [];
		$item['verb'] = ACTIVITY_LIKE;
		$item['parent-uri'] = $activity['object'];
		$item['gravity'] = GRAVITY_ACTIVITY;
		$item['object-type'] = ACTIVITY_OBJ_NOTE;

		self::postItem($activity, $item, $body);
	}

	private static function postItem($activity, $item, $body)
	{
		/// @todo What to do with $activity['context']?

		$item['network'] = Protocol::ACTIVITYPUB;
		$item['private'] = !in_array(0, $activity['receiver']);
		$item['author-id'] = Contact::getIdForURL($activity['author'], 0, true);
		$item['owner-id'] = Contact::getIdForURL($activity['owner'], 0, true);
		$item['uri'] = $activity['uri'];
		$item['created'] = $activity['published'];
		$item['edited'] = $activity['updated'];
		$item['guid'] = $activity['uuid'];
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

	private static function fetchMissingActivity($url, $child)
	{
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

	private static function getUserOfObject($object)
	{
		$self = DBA::selectFirst('contact', ['uid'], ['nurl' => normalise_link($object), 'self' => true]);
		if (!DBA::isResult($self)) {
			return false;
		} else {
			return $self['uid'];
		}
	}

	private static function followUser($activity)
	{
		$uid = self::getUserOfObject($activity['object']);
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

	private static function acceptFollowUser($activity)
	{
		$uid = self::getUserOfObject($activity['object']);
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

	private static function undoFollowUser($activity)
	{
		$uid = self::getUserOfObject($activity['object']);
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
