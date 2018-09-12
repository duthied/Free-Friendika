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
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Crypto;
use Friendica\Content\Text\BBCode;

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
 */
class ActivityPub
{
	const PUBLIC = 'https://www.w3.org/ns/activitystreams#Public';

	public static function transmit($content, $target, $uid)
	{
		$owner = User::getOwnerDataById($uid);

		if (!$owner) {
			return;
		}

		$host = parse_url($target, PHP_URL_HOST);
		$path = parse_url($target, PHP_URL_PATH);
		$date = date('r');

		$headers = ['Host: ' . $host, 'Date: ' . $date];

		$signed_data = "(request-target): post " . $path . "\nhost: " . $host . "\ndate: " . $date;

		$signature = base64_encode(Crypto::rsaSign($signed_data, $owner['uprvkey'], 'sha256'));

		$headers[] = 'Signature: keyId="' . $owner['url'] . '#main-key' . '",headers="(request-target) host date",signature="' . $signature . '"';
		$headers[] = 'Content-Type: application/activity+json';
//print_r($headers);
//die($signed_data);
//$headers = [];
//		$headers = HTTPSignature::createSig('', $headers, $owner['uprvkey'], $owner['url'] . '#main-key', false, false, 'sha256');

		Network::post($target, $content, $headers);
		$return_code = BaseObject::getApp()->get_curl_code();
echo $return_code."\n";
		print_r(BaseObject::getApp()->get_curl_headers());
		print_r($headers);
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
		$data['manuallyApprovesFollowers'] = false;
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
		$data['id'] = $item['plink'];
		$data['actor'] = $item['author-link'];
		$data['to'] = 'https://www.w3.org/ns/activitystreams#Public';
		$data['object'] = self::createNote($item);
//		print_r($data);
//		print_r($item);
		return $data;
	}

	public static function createNote($item)
	{
		$data = [];
		$data['type'] = 'Note';
		$data['id'] = $item['plink'];
		//$data['context'] = $data['conversation'] = $item['parent-uri'];
		$data['actor'] = $item['author-link'];
//		if (!$item['private']) {
//			$data['to'] = [];
//			$data['to'][] = '"https://pleroma.soykaf.com/users/heluecht"';
			$data['to'] = 'https://www.w3.org/ns/activitystreams#Public';
//			$data['cc'] = 'https://pleroma.soykaf.com/users/heluecht';
//		}
		$data['published'] = DateTimeFormat::utc($item["created"]."+00:00", DateTimeFormat::ATOM);
		$data['updated'] = DateTimeFormat::utc($item["edited"]."+00:00", DateTimeFormat::ATOM);
		$data['attributedTo'] = $item['author-link'];
		$data['title'] = BBCode::convert($item['title'], false, 7);
		$data['content'] = BBCode::convert($item['body'], false, 7);
		//$data['summary'] = '';
		//$data['sensitive'] = false;
		//$data['emoji'] = [];
		//$data['tag'] = [];
		//$data['attachment'] = [];
		return $data;
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
		$profile['alias'] = self::processElement($data, 'url', 'href');

		$parts = parse_url($profile['url']);
		unset($parts['scheme']);
		unset($parts['path']);
		$profile['addr'] = $profile['nick'] . '@' . str_replace('//', '', Network::unparseURL($parts));

		$profile['photo'] = self::processElement($data, 'icon', 'url');
		$profile['about'] = defaults($data, 'summary', '');
		$profile['batch'] = self::processElement($data, 'endpoints', 'sharedInbox');
		$profile['pubkey'] = self::processElement($data, 'publicKey', 'publicKeyPem');
		$profile['notify'] = $data['inbox'];
		$profile['poll'] = $data['outbox'];

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

		// Handled
		unset($data['id']);
		unset($data['inbox']);
		unset($data['outbox']);
		unset($data['preferredUsername']);
		unset($data['name']);
		unset($data['summary']);
		unset($data['url']);
		unset($data['publicKey']);
		unset($data['endpoints']);
		unset($data['icon']);
		unset($data['uuid']);

		// To-Do
		unset($data['type']);
		unset($data['manuallyApprovesFollowers']);

		// Unhandled
		unset($data['@context']);
		unset($data['tag']);
		unset($data['attachment']);
		unset($data['image']);
		unset($data['nomadicLocations']);
		unset($data['signature']);
		unset($data['following']);
		unset($data['followers']);
		unset($data['featured']);
		unset($data['movedTo']);
		unset($data['liked']);
		unset($data['sharedInbox']); // Misskey
		unset($data['isCat']); // Misskey
		unset($data['kroeg:blocks']); // Kroeg
		unset($data['updated']); // Kroeg

/*		if (!empty($data)) {
			print_r($data);
			die();
		}
*/
		return $profile;
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
			self::processActivity($activity, $url);
		}
	}

	function processActivity($activity, $url)
	{
		if (empty($activity['type'])) {
			return;
		}

		if (empty($activity['object'])) {
			return;
		}

		if (empty($activity['actor'])) {
			return;
		}

		$actor = self::processElement($activity, 'actor', 'id');
		if (empty($actor)) {
			return;
		}

		if (is_string($activity['object'])) {
			$object_url = $activity['object'];
		} elseif (!empty($activity['object']['id'])) {
			$object_url = $activity['object']['id'];
		} else {
			return;
		}

		$receivers = self::getReceivers($activity);
		if (empty($receivers)) {
			return;
		}

		// ----------------------------------
		// unhandled
		unset($activity['@context']);
		unset($activity['id']);

		// Non standard
		unset($activity['title']);
		unset($activity['atomUri']);
		unset($activity['context_id']);
		unset($activity['statusnetConversationId']);

		$structure = $activity;

		// To-Do?
		unset($activity['context']);
		unset($activity['location']);

		// handled
		unset($activity['to']);
		unset($activity['cc']);
		unset($activity['bto']);
		unset($activity['bcc']);
		unset($activity['type']);
		unset($activity['actor']);
		unset($activity['object']);
		unset($activity['published']);
		unset($activity['updated']);
		unset($activity['instrument']);
		unset($activity['inReplyTo']);

		if (!empty($activity)) {
			echo "Activity\n";
			print_r($activity);
			die($url."\n");
		}

		$activity = $structure;
		// ----------------------------------

		$item = self::fetchObject($object_url, $url);
		if (empty($item)) {
			return;
		}

		$item = self::addActivityFields($item, $activity);

		$item['owner'] = $actor;

		$item['receiver'] = array_merge($item['receiver'], $receivers);

		switch ($activity['type']) {
			case 'Create':
			case 'Update':
				self::createItem($item);
				break;

			case 'Announce':
				self::announceItem($item);
				break;

			case 'Like':
			case 'Dislike':
				self::activityItem($item);
				break;

			case 'Follow':
				break;

			default:
				echo "Unknown activity: ".$activity['type']."\n";
				print_r($item);
				die();
				break;
		}
	}

	private static function getReceivers($activity)
	{
		$receivers = [];

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
					$receivers[$receiver] = 0;
				}

				$condition = ['self' => true, 'nurl' => normalise_link($receiver)];
				$contact = DBA::selectFirst('contact', ['id'], $condition);
				if (!DBA::isResult($contact)) {
					continue;
				}
				$receivers[$receiver] = $contact['id'];
			}
		}
		return $receivers;
	}

	private static function addActivityFields($item, $activity)
	{
		if (!empty($activity['published']) && empty($item['published'])) {
			$item['published'] = $activity['published'];
		}

		if (!empty($activity['updated']) && empty($item['updated'])) {
			$item['updated'] = $activity['updated'];
		}

		if (!empty($activity['inReplyTo']) && empty($item['parent-uri'])) {
			$item['parent-uri'] = self::processElement($activity, 'inReplyTo', 'id');
		}

		if (!empty($activity['instrument'])) {
			$item['service'] = self::processElement($activity, 'instrument', 'name', 'Service');
		}

		// Remove all "null" fields
		foreach ($item as $field => $content) {
			if (is_null($content)) {
				unset($item[$field]);
			}
		}

		return $item;
	}

	private static function fetchObject($object_url, $url)
	{
		$data = self::fetchContent($object_url);
		if (empty($data)) {
			return false;
		}

		if (empty($data['type'])) {
			return false;
		} else {
			$type = $data['type'];
		}

		if (in_array($type, ['Note', 'Article', 'Video'])) {
			$common = self::processCommonData($data, $url);
		}

		switch ($type) {
			case 'Note':
				return array_merge($common, self::processNote($data, $url));
			case 'Article':
				return array_merge($common, self::processArticle($data, $url));
			case 'Video':
				return array_merge($common, self::processVideo($data, $url));

			case 'Announce':
				if (empty($data['object'])) {
					return false;
				}
				return self::fetchObject($data['object'], $url);

			case 'Person':
			case 'Tombstone':
				break;

			default:
				echo "Unknown object type: ".$data['type']."\n";
				print_r($data);
				die($url."\n");
				break;
		}
	}

	private static function processCommonData(&$object, $url)
	{
		if (empty($object['id']) || empty($object['attributedTo'])) {
			return false;
		}

		$item = [];
		$item['uri'] = $object['id'];

		if (!empty($object['inReplyTo'])) {
			$item['reply-to-uri'] = self::processElement($object, 'inReplyTo', 'id');
		} else {
			$item['reply-to-uri'] = $item['uri'];
		}

		$item['published'] = defaults($object, 'published', null);
		$item['updated'] = defaults($object, 'updated', $item['published']);

		if (empty($item['published']) && !empty($item['updated'])) {
			$item['published'] = $item['updated'];
		}

		$item['uuid'] = defaults($object, 'uuid', null);
		$item['owner'] = $item['author'] = self::processElement($object, 'attributedTo', 'id');
		$item['context'] = defaults($object, 'context', null);
		$item['conversation'] = defaults($object, 'conversation', null);
		$item['sensitive'] = defaults($object, 'sensitive', null);
		$item['name'] = defaults($object, 'name', null);
		$item['title'] = defaults($object, 'title', null);
		$item['content'] = defaults($object, 'content', null);
		$item['summary'] = defaults($object, 'summary', null);
		$item['location'] = self::processElement($object, 'location', 'name', 'Place');
		$item['attachments'] = defaults($object, 'attachment', null);
		$item['tags'] = defaults($object, 'tag', null);
		$item['service'] = self::processElement($object, 'instrument', 'name', 'Service');
		$item['alternate-url'] = self::processElement($object, 'url', 'href');
		$item['receiver'] = self::getReceivers($object);

		// handled
		unset($object['id']);
		unset($object['inReplyTo']);
		unset($object['published']);
		unset($object['updated']);
		unset($object['uuid']);
		unset($object['attributedTo']);
		unset($object['context']);
		unset($object['conversation']);
		unset($object['sensitive']);
		unset($object['name']);
		unset($object['title']);
		unset($object['content']);
		unset($object['summary']);
		unset($object['location']);
		unset($object['attachment']);
		unset($object['tag']);
		unset($object['instrument']);
		unset($object['url']);
		unset($object['to']);
		unset($object['cc']);
		unset($object['bto']);
		unset($object['bcc']);

		// To-Do
		unset($object['source']);

		// Unhandled
		unset($object['@context']);
		unset($object['type']);
		unset($object['actor']);
		unset($object['signature']);
		unset($object['mediaType']);
		unset($object['duration']);
		unset($object['replies']);
		unset($object['icon']);

		/*
		audience, preview, endTime, startTime, generator, image
		*/

		return $item;
	}

	private static function processNote($object, $url)
	{
		$item = [];

		// To-Do?
		unset($object['emoji']);
		unset($object['atomUri']);
		unset($object['inReplyToAtomUri']);

		// Unhandled
		unset($object['contentMap']);
		unset($object['announcement_count']);
		unset($object['announcements']);
		unset($object['context_id']);
		unset($object['likes']);
		unset($object['like_count']);
		unset($object['inReplyToStatusId']);
		unset($object['shares']);
		unset($object['quoteUrl']);
		unset($object['statusnetConversationId']);

		if (empty($object))
			return $item;

		echo "Unknown Note\n";
		print_r($object);
		print_r($item);
		die($url."\n");

		return [];
	}

	private static function processArticle($object, $url)
	{
		$item = [];

		if (empty($object))
			return $item;

		echo "Unknown Article\n";
		print_r($object);
		print_r($item);
		die($url."\n");

		return [];
	}

	private static function processVideo($object, $url)
	{
		$item = [];

		// To-Do?
		unset($object['category']);
		unset($object['licence']);
		unset($object['language']);
		unset($object['commentsEnabled']);

		// Unhandled
		unset($object['views']);
		unset($object['waitTranscoding']);
		unset($object['state']);
		unset($object['support']);
		unset($object['subtitleLanguage']);
		unset($object['likes']);
		unset($object['dislikes']);
		unset($object['shares']);
		unset($object['comments']);

		if (empty($object))
			return $item;

		echo "Unknown Video\n";
		print_r($object);
		print_r($item);
		die($url."\n");

		return [];
	}

	private static function processElement($array, $element, $key, $type = null)
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

		if (is_null($type)) {
			if (!empty($array[$element][$key])) {
				return $array[$element][$key];
			}

			if (!empty($array[$element][0][$key])) {
				return $array[$element][0][$key];
			}

			return false;
		}

		if (!empty($array[$element][$key]) && !empty($array[$element]['type']) && ($array[$element]['type'] == $type)) {
			return $array[$element][$key];
		}

		/// @todo Add array search

		return false;
	}

	private static function createItem($item)
	{
//		print_r($item);
	}

	private static function announceItem($item)
	{
//		print_r($item);
	}

	private static function activityItem($item)
	{
	//	print_r($item);
	}

}
