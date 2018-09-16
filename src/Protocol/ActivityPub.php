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
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Crypto;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Network\Probe;

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
 * - Activities: Like, Dislike, Update, Delete
 * - Object Tyoes: Article, Announce, Person, Tombstone
 *
 * General:
 * - Message distribution
 * - Endpoints: Outbox, Object, Follower, Following
 * - General cleanup
 */
class ActivityPub
{
	const PUBLIC = 'https://www.w3.org/ns/activitystreams#Public';

	public static function transmit($data, $target, $uid)
	{
		$owner = User::getOwnerDataById($uid);

		if (!$owner) {
			return;
		}

		$content = json_encode($data);

		// Header data that is about to be signed.
		/// @todo Add "digest"
		$host = parse_url($target, PHP_URL_HOST);
		$path = parse_url($target, PHP_URL_PATH);
		$date = date('r');
		$content_length = strlen($content);

		$headers = ['Host: ' . $host, 'Date: ' . $date, 'Content-Length: ' . $content_length];

		$signed_data = "(request-target): post " . $path . "\nhost: " . $host . "\ndate: " . $date . "\ncontent-length: " . $content_length;

		$signature = base64_encode(Crypto::rsaSign($signed_data, $owner['uprvkey'], 'sha256'));

		$headers[] = 'Signature: keyId="' . $owner['url'] . '#main-key' . '",headers="(request-target) host date content-length",signature="' . $signature . '"';
		$headers[] = 'Content-Type: application/activity+json';

		Network::post($target, $content, $headers);
		$return_code = BaseObject::getApp()->get_curl_code();

		logger('Transmit to ' . $target . ' returned ' . $return_code);
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
		$fields = ['guid', 'nickname', 'pubkey', 'account-type'];
		$user = DBA::selectFirst('user', $fields, $condition);
		if (!DBA::isResult($user)) {
			return [];
		}

		$fields = ['locality', 'region', 'country-name', 'page-flags'];
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
			['uuid' => 'http://schema.org/identifier', 'sensitive' => 'as:sensitive',
			'vcard' => 'http://www.w3.org/2006/vcard/ns#']]];

		$data['id'] = $contact['url'];
		$data['uuid'] = $user['guid'];
		$data['type'] = $accounttype[$user['account-type']];
		$data['following'] = System::baseUrl() . '/following/' . $user['nickname'];
		$data['followers'] = System::baseUrl() . '/followers/' . $user['nickname'];
		$data['inbox'] = System::baseUrl() . '/inbox/' . $user['nickname'];
		$data['outbox'] = System::baseUrl() . '/outbox/' . $user['nickname'];
		$data['preferredUsername'] = $user['nickname'];
		$data['name'] = $contact['name'];
		$data['vcard:hasAddress'] = ['@type' => 'Home', 'vcard:country-name' => $profile['country-name'],
			'vcard:region' => $profile['region'], 'vcard:locality' => $profile['locality']];
		$data['summary'] = $contact['about'];
		$data['url'] = $contact['url'];
		$data['manuallyApprovesFollowers'] = in_array($profile['page-flags'], [Contact::PAGE_NORMAL, Contact::PAGE_PRVGROUP]);
		$data['publicKey'] = ['id' => $contact['url'] . '#main-key',
			'owner' => $contact['url'],
			'publicKeyPem' => $user['pubkey']];
		$data['endpoints'] = ['sharedInbox' => System::baseUrl() . '/inbox'];
		$data['icon'] = ['type' => 'Image',
			'url' => $contact['avatar']];

		// tags: https://kitty.town/@inmysocks/100656097926961126.json
		return $data;
	}

	public static function createActivityFromItem($item_id)
	{
		$item = Item::selectFirst([], ['id' => $item_id]);

		$data = ['@context' => ['https://www.w3.org/ns/activitystreams', 'https://w3id.org/security/v1',
			['Emoji' => 'toot:Emoji', 'Hashtag' => 'as:Hashtag', 'atomUri' => 'ostatus:atomUri',
			'conversation' => 'ostatus:conversation', 'inReplyToAtomUri' => 'ostatus:inReplyToAtomUri',
			'ostatus' => 'http://ostatus.org#', 'sensitive' => 'as:sensitive',
			'toot' => 'http://joinmastodon.org/ns#']]];

		$data['type'] = 'Create';
		$data['id'] = $item['uri'];
		$data['actor'] = $item['author-link'];
		$data['to'] = 'https://www.w3.org/ns/activitystreams#Public';
		$data['object'] = self::createNote($item);
		return $data;
	}

	public static function createNote($item)
	{
		$data = [];
		$data['type'] = 'Note';
		$data['id'] = $item['uri'];

		if ($item['uri'] != $item['thr-parent']) {
			$data['inReplyTo'] = $item['thr-parent'];
		}

		$conversation = DBA::selectFirst('conversation', ['conversation-uri'], ['item-uri' => $item['parent-uri']]);
		if (DBA::isResult($conversation) && !empty($conversation['conversation-uri'])) {
			$conversation_uri = $conversation['conversation-uri'];
		} else {
			$conversation_uri = $item['parent-uri'];
		}

		$data['context'] = $data['conversation'] = $conversation_uri;
		$data['actor'] = $item['author-link'];
		$data['to'] = [];
		if (!$item['private']) {
			$data['to'][] = 'https://www.w3.org/ns/activitystreams#Public';
		}
		$data['published'] = DateTimeFormat::utc($item["created"]."+00:00", DateTimeFormat::ATOM);
		$data['updated'] = DateTimeFormat::utc($item["edited"]."+00:00", DateTimeFormat::ATOM);
		$data['attributedTo'] = $item['author-link'];
		$data['name'] = BBCode::convert($item['title'], false, 7);
		$data['content'] = BBCode::convert($item['body'], false, 7);
		$data['source'] = ['content' => $item['body'], 'mediaType' => "text/bbcode"];
		//$data['summary'] = ''; // Ignore by now
		//$data['sensitive'] = false; // - Query NSFW
		//$data['emoji'] = []; // Ignore by now
		//$data['tag'] = []; /// @ToDo
		//$data['attachment'] = []; // @ToDo
		return $data;
	}

	public static function transmitActivity($activity, $target, $uid)
	{
		$profile = Probe::uri($target, Protocol::ACTIVITYPUB);

		$owner = User::getOwnerDataById($uid);

		$data = ['@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => System::baseUrl() . '/activity/' . System::createGUID(),
			'type' => $activity,
			'actor' => $owner['url'],
			'object' => $profile['url']];

		logger('Sending activity ' . $activity . ' to ' . $target . ' for user ' . $uid, LOGGER_DEBUG);
		return self::transmit($data,  $profile['notify'], $uid);
	}

	public static function transmitContactAccept($target, $id, $uid)
	{
		$profile = Probe::uri($target, Protocol::ACTIVITYPUB);

		$owner = User::getOwnerDataById($uid);
		$data = ['@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => System::baseUrl() . '/activity/' . System::createGUID(),
			'type' => 'Accept',
			'actor' => $owner['url'],
			'object' => ['id' => $id, 'type' => 'Follow',
				'actor' => $profile['url'],
				'object' => $owner['url']]];

		logger('Sending accept to ' . $target . ' for user ' . $uid . ' with id ' . $id, LOGGER_DEBUG);
		return self::transmit($data,  $profile['notify'], $uid);
	}

	public static function transmitContactReject($target, $id, $uid)
	{
		$profile = Probe::uri($target, Protocol::ACTIVITYPUB);

		$owner = User::getOwnerDataById($uid);
		$data = ['@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => System::baseUrl() . '/activity/' . System::createGUID(),
			'type' => 'Reject',
			'actor' => $owner['url'],
			'object' => ['id' => $id, 'type' => 'Follow',
				'actor' => $profile['url'],
				'object' => $owner['url']]];

		logger('Sending reject to ' . $target . ' for user ' . $uid . ' with id ' . $id, LOGGER_DEBUG);
		return self::transmit($data,  $profile['notify'], $uid);
	}

	public static function transmitContactUndo($target, $uid)
	{
		$profile = Probe::uri($target, Protocol::ACTIVITYPUB);

		$id = System::baseUrl() . '/activity/' . System::createGUID();

		$owner = User::getOwnerDataById($uid);
		$data = ['@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => $id,
			'type' => 'Undo',
			'actor' => $owner['url'],
			'object' => ['id' => $id, 'type' => 'Follow',
				'actor' => $owner['url'],
				'object' => $profile['url']]];

		logger('Sending undo to ' . $target . ' for user ' . $uid . ' with id ' . $id, LOGGER_DEBUG);
		return self::transmit($data,  $profile['notify'], $uid);
	}

	/**
	 * Fetches ActivityPub content from the given url
	 *
	 * @param string $url content url
	 * @return array
	 */
	public static function fetchContent($url)
	{
		$ret = Network::curl($url, false, $redirects, ['accept_content' => 'application/activity+json']);
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

	public static function verifySignature($content, $http_headers)
	{
		$object = json_decode($content, true);

		if (empty($object)) {
			return false;
		}

		$actor = self::processElement($object, 'actor', 'id');

		$headers = [];
		$headers['(request-target)'] = strtolower($http_headers['REQUEST_METHOD']) . ' ' . $http_headers['REQUEST_URI'];

		// First take every header
		foreach ($http_headers as $k => $v) {
			$field = str_replace('_', '-', strtolower($k));
			$headers[$field] = $v;
		}

		// Now add every http header
		foreach ($http_headers as $k => $v) {
			if (strpos($k, 'HTTP_') === 0) {
				$field = str_replace('_', '-', strtolower(substr($k, 5)));
				$headers[$field] = $v;
			}
		}

		$sig_block = ActivityPub::parseSigHeader($http_headers['HTTP_SIGNATURE']);

		if (empty($sig_block) || empty($sig_block['headers']) || empty($sig_block['keyId'])) {
			return false;
		}

		$signed_data = '';
		foreach ($sig_block['headers'] as $h) {
			if (array_key_exists($h, $headers)) {
				$signed_data .= $h . ': ' . $headers[$h] . "\n";
			}
		}
		$signed_data = rtrim($signed_data, "\n");

		if (empty($signed_data)) {
			return false;
		}

		$algorithm = null;

		if ($sig_block['algorithm'] === 'rsa-sha256') {
			$algorithm = 'sha256';
		}

		if ($sig_block['algorithm'] === 'rsa-sha512') {
			$algorithm = 'sha512';
		}

		if (empty($algorithm)) {
			return false;
		}

		$key = self::fetchKey($sig_block['keyId'], $actor);

		if (empty($key)) {
			return false;
		}

		if (!Crypto::rsaVerify($signed_data, $sig_block['signature'], $key, $algorithm)) {
			return false;
		}

		// Check the digest when it is part of the signed data
		if (in_array('digest', $sig_block['headers'])) {
			$digest = explode('=', $headers['digest'], 2);
			if ($digest[0] === 'SHA-256') {
				$hashalg = 'sha256';
			}
			if ($digest[0] === 'SHA-512') {
				$hashalg = 'sha512';
			}

			/// @todo add all hashes from the rfc

			if (!empty($hashalg) && base64_encode(hash($hashalg, $content, true)) != $digest[1]) {
				return false;
			}
		}

		// Check the content-length when it is part of the signed data
		if (in_array('content-length', $sig_block['headers'])) {
			if (strlen($content) != $headers['content-length']) {
				return false;
			}
		}

		return true;

	}

	private static function fetchKey($id, $actor)
	{
		$url = (strpos($id, '#') ? substr($id, 0, strpos($id, '#')) : $id);

		$profile = Probe::uri($url, Protocol::ACTIVITYPUB);
		if (!empty($profile)) {
			return $profile['pubkey'];
		} elseif ($url != $actor) {
			$profile = Probe::uri($actor, Protocol::ACTIVITYPUB);
			if (!empty($profile)) {
				return $profile['pubkey'];
			}
		}

		return false;
	}

	/**
	 * @brief
	 *
	 * @param string $header
	 * @return array associate array with
	 *   - \e string \b keyID
	 *   - \e string \b algorithm
	 *   - \e array  \b headers
	 *   - \e string \b signature
	 */
	private static function parseSigHeader($header)
	{
		$ret = [];
		$matches = [];

		if (preg_match('/keyId="(.*?)"/ism',$header,$matches)) {
			$ret['keyId'] = $matches[1];
		}

		if (preg_match('/algorithm="(.*?)"/ism',$header,$matches)) {
			$ret['algorithm'] = $matches[1];
		}

		if (preg_match('/headers="(.*?)"/ism',$header,$matches)) {
			$ret['headers'] = explode(' ', $matches[1]);
		}

		if (preg_match('/signature="(.*?)"/ism',$header,$matches)) {
			$ret['signature'] = base64_decode(preg_replace('/\s+/','',$matches[1]));
		}

		return $ret;
	}

	/**
	 * Fetches a profile from the given url
	 *
	 * @param string $url profile url
	 * @return array
	 */
	public static function fetchProfile($url)
	{
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

		$profile = ['network' => Protocol::ACTIVITYPUB];
		$profile['nick'] = $data['preferredUsername'];
		$profile['name'] = defaults($data, 'name', $profile['nick']);
		$profile['guid'] = defaults($data, 'uuid', null);
		$profile['url'] = $data['id'];

		$parts = parse_url($profile['url']);
		unset($parts['scheme']);
		unset($parts['path']);
		$profile['addr'] = $profile['nick'] . '@' . str_replace('//', '', Network::unparseURL($parts));
		$profile['alias'] = self::processElement($data, 'url', 'href');
		$profile['photo'] = self::processElement($data, 'icon', 'url');
		// $profile['community']
		// $profile['keywords']
		// $profile['location']
		$profile['about'] = defaults($data, 'summary', '');
		$profile['batch'] = self::processElement($data, 'endpoints', 'sharedInbox');
		$profile['notify'] = $data['inbox'];
		$profile['poll'] = $data['outbox'];
		$profile['pubkey'] = self::processElement($data, 'publicKey', 'publicKeyPem');

		// Check if the address is resolvable
		if (self::addrToUrl($profile['addr']) == $profile['url']) {
			$parts = parse_url($profile['url']);
			unset($parts['path']);
			$profile['baseurl'] = Network::unparseURL($parts);
		} else {
			unset($profile['addr']);
		}

		if ($profile['url'] == $profile['alias']) {
			unset($profile['alias']);
		}

		// Remove all "null" fields
		foreach ($profile as $field => $content) {
			if (is_null($content)) {
				unset($profile[$field]);
			}
		}

		// To-Do
		// type, manuallyApprovesFollowers

		// Unhandled
		// @context, tag, attachment, image, nomadicLocations, signature, following, followers, featured, movedTo, liked

		// Unhandled from Misskey
		// sharedInbox, isCat

		// Unhandled from Kroeg
		// kroeg:blocks, updated

		return $profile;
	}

	public static function processInbox($body, $header, $uid)
	{
		logger('Incoming message for user ' . $uid, LOGGER_DEBUG);

		if (!self::verifySignature($body, $header)) {
			logger('Invalid signature, message will be discarded.', LOGGER_DEBUG);
			return;
		}

		$activity = json_decode($body, true);

		if (!is_array($activity)) {
			logger('Invalid body.', LOGGER_DEBUG);
			return;
		}

		self::processActivity($activity, $body, $uid);
	}

	public static function fetchOutbox($url)
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
			self::fetchOutbox($data['first']);
			return;
		} else {
			$items = [];
		}

		foreach ($items as $activity) {
			self::processActivity($activity);
		}
	}

	private static function prepareObjectData($activity, $uid)
	{
		$actor = self::processElement($activity, 'actor', 'id');
		if (empty($actor)) {
			logger('Empty actor', LOGGER_DEBUG);
			return [];
		}

		// Fetch all receivers from to, cc, bto and bcc
		$receivers = self::getReceivers($activity, $actor);

		// When it is a delivery to a personal inbox we add that user to the receivers
		if (!empty($uid)) {
			$owner = User::getOwnerDataById($uid);
			$additional = [$owner['url'] => $uid];
			$receivers = array_merge($receivers, $additional);
		}

		logger('Receivers: ' . json_encode($receivers), LOGGER_DEBUG);

		$public = in_array(0, $receivers);

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
			$object_data = self::fetchObject($object_url, $activity['object']);
			if (empty($object_data)) {
				logger("Object data couldn't be processed", LOGGER_DEBUG);
				return [];
			}
		} elseif ($activity['type'] == 'Accept') {
			$object_data = [];
			$object_data['object_type'] = self::processElement($activity, 'object', 'type');
			$object_data['object'] = self::processElement($activity, 'object', 'actor');
		} elseif ($activity['type'] == 'Undo') {
			$object_data = [];
			$object_data['object_type'] = self::processElement($activity, 'object', 'type');
			$object_data['object'] = self::processElement($activity, 'object', 'object');
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

	private static function processActivity($activity, $body = '', $uid = null)
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

		$object_data = self::prepareObjectData($activity, $uid);
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

		$data = self::fetchContent($actor);
		$followers = defaults($data, 'followers', '');

		logger('Actor: ' . $actor . ' - Followers: ' . $followers, LOGGER_DEBUG);

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

				if (in_array($receiver, [$followers, self::PUBLIC])) {
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
				$receivers['cid:' . $contact['uid']] = $contact['uid'];
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
			$object_data['parent-uri'] = self::processElement($activity, 'inReplyTo', 'id');
		}

		if (!empty($activity['instrument'])) {
			$object_data['service'] = self::processElement($activity, 'instrument', 'name', 'type', 'Service');
		}
		return $object_data;
	}

	private static function fetchObject($object_url, $object = [], $public = true)
	{
		if ($public) {
			$data = self::fetchContent($object_url);
			if (empty($data)) {
				logger('Empty content for ' . $object_url . ', check if content is available locally.', LOGGER_DEBUG);
				$data = $object_url;
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
			$data = self::createNote($item);
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
			$object_data['reply-to-uri'] = self::processElement($object, 'inReplyTo', 'id');
		} else {
			$object_data['reply-to-uri'] = $object_data['uri'];
		}

		$object_data['published'] = defaults($object, 'published', null);
		$object_data['updated'] = defaults($object, 'updated', $object_data['published']);

		if (empty($object_data['published']) && !empty($object_data['updated'])) {
			$object_data['published'] = $object_data['updated'];
		}

		$object_data['uuid'] = defaults($object, 'uuid', null);
		$object_data['owner'] = $object_data['author'] = self::processElement($object, 'attributedTo', 'id');
		$object_data['context'] = defaults($object, 'context', null);
		$object_data['conversation'] = defaults($object, 'conversation', null);
		$object_data['sensitive'] = defaults($object, 'sensitive', null);
		$object_data['name'] = defaults($object, 'title', null);
		$object_data['name'] = defaults($object, 'name', $object_data['name']);
		$object_data['summary'] = defaults($object, 'summary', null);
		$object_data['content'] = defaults($object, 'content', null);
		$object_data['source'] = defaults($object, 'source', null);
		$object_data['location'] = self::processElement($object, 'location', 'name', 'type', 'Place');
		$object_data['attachments'] = defaults($object, 'attachment', null);
		$object_data['tags'] = defaults($object, 'tag', null);
		$object_data['service'] = self::processElement($object, 'instrument', 'name', 'type', 'Service');
		$object_data['alternate-url'] = self::processElement($object, 'url', 'href');
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

	private static function processElement($array, $element, $key, $type = null, $type_value = null)
	{
		if (empty($array)) {
			return false;
		}

		if (empty($array[$element])) {
			return false;
		}

		if (is_string($array[$element])) {
			return $array[$element];
		}

		if (is_null($type_value)) {
			if (!empty($array[$element][$key])) {
				return $array[$element][$key];
			}

			if (!empty($array[$element][0][$key])) {
				return $array[$element][0][$key];
			}

			return false;
		}

		if (!empty($array[$element][$key]) && !empty($array[$element][$type]) && ($array[$element][$type] == $type_value)) {
			return $array[$element][$key];
		}

		/// @todo Add array search

		return false;
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
			self::fetchMissingActivity($activity['reply-to-uri']);
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

		$source = self::processElement($activity, 'source', 'content', 'mediaType', 'text/bbcode');
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

	private static function fetchMissingActivity($url)
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
		$activity['actor'] = $object['attributedTo'];
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
