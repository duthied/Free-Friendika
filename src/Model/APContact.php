<?php

/**
 * @file src/Model/APContact.php
 */

namespace Friendica\Model;

use Friendica\BaseObject;
use Friendica\Database\DBA;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\Network;
use Friendica\Util\JsonLD;
use Friendica\Util\DateTimeFormat;

require_once 'boot.php';

class APContact extends BaseObject
{
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
	 * Fetches a profile from a given url
	 *
	 * @param string  $url    profile url
	 * @param boolean $update true = always update, false = never update, null = update when not found
	 * @return array profile array
	 */
	public static function getProfileByURL($url, $update = null)
	{
		if (empty($url)) {
			return false;
		}

		if (empty($update)) {
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

			if (!is_null($update)) {
				return false;
			}
		}

		if (empty(parse_url($url, PHP_URL_SCHEME))) {
			$url = self::addrToUrl($url);
			if (empty($url)) {
				return false;
			}
		}

		$data = ActivityPub::fetchContent($url);

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
			$apcontact['baseurl'] = null;
		}

		if ($apcontact['url'] == $apcontact['alias']) {
			$apcontact['alias'] = null;
		}

		$apcontact['updated'] = DateTimeFormat::utcNow();

		DBA::update('apcontact', $apcontact, ['url' => $url], true);

		// Update some data in the contact table with various ways to catch them all
		$contact_fields = ['name' => $apcontact['name'], 'about' => $apcontact['about']];
		DBA::update('contact', $contact_fields, ['nurl' => normalise_link($url)]);

		$contacts = DBA::select('contact', ['uid', 'id'], ['nurl' => normalise_link($url)]);
		while ($contact = DBA::fetch($contacts)) {
			Contact::updateAvatar($apcontact['photo'], $contact['uid'], $contact['id']);
		}
		DBA::close($contacts);

		// Update the gcontact table
		DBA::update('gcontact', $contact_fields, ['nurl' => normalise_link($url)]);

		logger('Updated profile for ' . $url, LOGGER_DEBUG);

		return $apcontact;
	}
}
