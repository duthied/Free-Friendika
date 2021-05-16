<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Model;

use Friendica\Content\Text\HTML;
use Friendica\Core\Cache\Duration;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Network\Probe;
use Friendica\Protocol\ActivityNamespace;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\HTTPSignature;
use Friendica\Util\JsonLD;
use Friendica\Util\Network;

class APContact
{
	/**
	 * Fetch webfinger data
	 *
	 * @param string $addr Address
	 * @return array webfinger data
	 */
	private static function fetchWebfingerData(string $addr)
	{
		$addr_parts = explode('@', $addr);
		if (count($addr_parts) != 2) {
			return [];
		}

		$data = ['addr' => $addr];
		$template = 'https://' . $addr_parts[1] . '/.well-known/webfinger?resource=acct:' . urlencode($addr);
		$webfinger = Probe::webfinger(str_replace('{uri}', urlencode($addr), $template), 'application/jrd+json');
		if (empty($webfinger['links'])) {
			$template = 'http://' . $addr_parts[1] . '/.well-known/webfinger?resource=acct:' . urlencode($addr);
			$webfinger = Probe::webfinger(str_replace('{uri}', urlencode($addr), $template), 'application/jrd+json');
			if (empty($webfinger['links'])) {
				return [];
			}
			$data['baseurl'] = 'http://' . $addr_parts[1];
		} else {
			$data['baseurl'] = 'https://' . $addr_parts[1];
		}

		foreach ($webfinger['links'] as $link) {
			if (empty($link['rel'])) {
				continue;
			}

			if (!empty($link['template']) && ($link['rel'] == ActivityNamespace::OSTATUSSUB)) {
				$data['subscribe'] = $link['template'];
			}

			if (!empty($link['href']) && !empty($link['type']) && ($link['rel'] == 'self') && ($link['type'] == 'application/activity+json')) {
				$data['url'] = $link['href'];
			}

			if (!empty($link['href']) && !empty($link['type']) && ($link['rel'] == 'http://webfinger.net/rel/profile-page') && ($link['type'] == 'text/html')) {
				$data['alias'] = $link['href'];
			}
		}

		if (!empty($data['url']) && !empty($data['alias']) && ($data['url'] == $data['alias'])) {
			unset($data['alias']);
		}

		return $data;
	}

	/**
	 * Fetches a profile from a given url
	 *
	 * @param string  $url    profile url
	 * @param boolean $update true = always update, false = never update, null = update when not found or outdated
	 * @return array profile array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getByURL($url, $update = null)
	{
		if (empty($url)) {
			return [];
		}

		$fetched_contact = false;

		if (empty($update)) {
			if (is_null($update)) {
				$ref_update = DateTimeFormat::utc('now - 1 month');
			} else {
				$ref_update = DBA::NULL_DATETIME;
			}

			$apcontact = DBA::selectFirst('apcontact', [], ['url' => $url]);
			if (!DBA::isResult($apcontact)) {
				$apcontact = DBA::selectFirst('apcontact', [], ['alias' => $url]);
			}

			if (!DBA::isResult($apcontact)) {
				$apcontact = DBA::selectFirst('apcontact', [], ['addr' => $url]);
			}

			if (DBA::isResult($apcontact) && ($apcontact['updated'] > $ref_update) && !empty($apcontact['pubkey'])) {
				return $apcontact;
			}

			if (!is_null($update)) {
				return DBA::isResult($apcontact) ? $apcontact : [];
			}

			if (DBA::isResult($apcontact)) {
				$fetched_contact = $apcontact;
			}
		}

		$apcontact = [];

		$webfinger = empty(parse_url($url, PHP_URL_SCHEME));
		if ($webfinger) {
			$apcontact = self::fetchWebfingerData($url);
			if (empty($apcontact['url'])) {
				return $fetched_contact;
			}
			$url = $apcontact['url'];
		}

		$curlResult = HTTPSignature::fetchRaw($url);
		$failed = empty($curlResult) || empty($curlResult->getBody()) ||
			(!$curlResult->isSuccess() && ($curlResult->getReturnCode() != 410));

		if (!$failed) {
			$data = json_decode($curlResult->getBody(), true);
			$failed = empty($data) || !is_array($data);
		}

		if (!$failed && ($curlResult->getReturnCode() == 410)) {
			$data = ['@context' => ActivityPub::CONTEXT, 'id' => $url, 'type' => 'Tombstone'];
		}

		if ($failed) {
			self::markForArchival($fetched_contact ?: []);
			return $fetched_contact;
		}

		if (empty($data['id'])) {
			return $fetched_contact;
		}
		
		// Detect multiple fast repeating request to the same address
		// See https://github.com/friendica/friendica/issues/9303
		$cachekey = 'apcontact:getByURL:' . $url;
		$result = DI::cache()->get($cachekey);
		if (!is_null($result)) {
			Logger::notice('Multiple requests for the address', ['url' => $url, 'update' => $update, 'callstack' => System::callstack(20), 'result' => $result]);
		} else {
			DI::cache()->set($cachekey, System::callstack(20), Duration::FIVE_MINUTES);
		}

		$apcontact['url'] = $data['id'];
		$apcontact['uuid'] = JsonLD::fetchElement($data, 'diaspora:guid');
		$apcontact['type'] = JsonLD::fetchElement($data, 'type');
		$apcontact['following'] = JsonLD::fetchElement($data, 'following');
		$apcontact['followers'] = JsonLD::fetchElement($data, 'followers');
		$apcontact['inbox'] = JsonLD::fetchElement($data, 'inbox');
		self::unarchiveInbox($apcontact['inbox'], false);
		$apcontact['outbox'] = JsonLD::fetchElement($data, 'outbox');

		$apcontact['sharedinbox'] = '';
		if (!empty($data['endpoints'])) {
			$apcontact['sharedinbox'] = JsonLD::fetchElement($data['endpoints'], 'sharedInbox');
			self::unarchiveInbox($apcontact['sharedinbox'], true);
		}

		$apcontact['nick'] = JsonLD::fetchElement($data, 'preferredUsername') ?? '';
		$apcontact['name'] = JsonLD::fetchElement($data, 'name');

		if (empty($apcontact['name'])) {
			$apcontact['name'] = $apcontact['nick'];
		}

		$apcontact['about'] = HTML::toBBCode(JsonLD::fetchElement($data, 'summary'));

		$apcontact['photo'] = JsonLD::fetchElement($data, 'icon');
		if (is_array($apcontact['photo']) || !empty($data['icon']['url'])) {
			$apcontact['photo'] = JsonLD::fetchElement($data['icon'], 'url');
		}

		if (empty($apcontact['alias'])) {
			$apcontact['alias'] = JsonLD::fetchElement($data, 'url');
			if (is_array($apcontact['alias'])) {
				$apcontact['alias'] = JsonLD::fetchElement($data['url'], 'href');
			}
		}

		// Quit if none of the basic values are set
		if (empty($apcontact['url']) || empty($apcontact['type']) || (($apcontact['type'] != 'Tombstone') && empty($apcontact['inbox']))) {
			return $fetched_contact;
		} elseif ($apcontact['type'] == 'Tombstone') {
			// The "inbox" field must have a content
			$apcontact['inbox'] = '';
		}

		// Quit if this doesn't seem to be an account at all
		if (!in_array($apcontact['type'], ActivityPub::ACCOUNT_TYPES)) {
			return $fetched_contact;
		}

		$parts = parse_url($apcontact['url']);
		unset($parts['scheme']);
		unset($parts['path']);

		if (empty($apcontact['addr'])) {
			if (!empty($apcontact['nick']) && is_array($parts)) {
				$apcontact['addr'] = $apcontact['nick'] . '@' . str_replace('//', '', Network::unparseURL($parts));
			} else {
				$apcontact['addr'] = '';
			}
		}

		$apcontact['pubkey'] = null;
		if (!empty($data['publicKey'])) {
			$apcontact['pubkey'] = trim(JsonLD::fetchElement($data['publicKey'], 'publicKeyPem'));
			if (strstr($apcontact['pubkey'], 'RSA ')) {
				$apcontact['pubkey'] = Crypto::rsaToPem($apcontact['pubkey']);
			}
		}

		$apcontact['manually-approve'] = (int)JsonLD::fetchElement($data, 'manuallyApprovesFollowers');

		if (!empty($data['generator'])) {
			$apcontact['baseurl'] = JsonLD::fetchElement($data['generator'], 'url');
			$apcontact['generator'] = JsonLD::fetchElement($data['generator'], 'name');
		}

		if (!empty($apcontact['following'])) {
			$content = ActivityPub::fetchContent($apcontact['following']);
			if (!empty($content)) {
				if (!empty($content['totalItems'])) {
					$apcontact['following_count'] = $content['totalItems'];
				}
			}
		}

		if (!empty($apcontact['followers'])) {
			$content = ActivityPub::fetchContent($apcontact['followers']);
			if (!empty($content)) {
				if (!empty($content['totalItems'])) {
					$apcontact['followers_count'] = $content['totalItems'];
				}
			}
		}

		if (!empty($apcontact['outbox'])) {
			$content = ActivityPub::fetchContent($apcontact['outbox']);
			if (!empty($content)) {
				if (!empty($content['totalItems'])) {
					$apcontact['statuses_count'] = $content['totalItems'];
				}
			}
		}

		// To-Do

		// Unhandled
		// tag, attachment, image, nomadicLocations, signature, featured, movedTo, liked

		// Unhandled from Misskey
		// sharedInbox, isCat

		// Unhandled from Kroeg
		// kroeg:blocks, updated

		// When the photo is too large, try to shorten it by removing parts
		if (strlen($apcontact['photo']) > 255) {
			$parts = parse_url($apcontact['photo']);
			unset($parts['fragment']);
			$apcontact['photo'] = Network::unparseURL($parts);

			if (strlen($apcontact['photo']) > 255) {
				unset($parts['query']);
				$apcontact['photo'] = Network::unparseURL($parts);
			}

			if (strlen($apcontact['photo']) > 255) {
				$apcontact['photo'] = substr($apcontact['photo'], 0, 255);
			}
		}

		if (!$webfinger && !empty($apcontact['addr'])) {
			$data = self::fetchWebfingerData($apcontact['addr']);
			if (!empty($data)) {
				$apcontact['baseurl'] = $data['baseurl'];

				if (empty($apcontact['alias']) && !empty($data['alias'])) {
					$apcontact['alias'] = $data['alias'];
				}
				if (!empty($data['subscribe'])) {
					$apcontact['subscribe'] = $data['subscribe'];
				}
			} else {
				$apcontact['addr'] = null;
			}
		}

		if (empty($apcontact['baseurl'])) {
			$apcontact['baseurl'] = null;
		}

		if (empty($apcontact['subscribe'])) {
			$apcontact['subscribe'] = null;
		}		

		if (!empty($apcontact['baseurl']) && empty($fetched_contact['gsid'])) {
			$apcontact['gsid'] = GServer::getID($apcontact['baseurl']);
		} elseif (!empty($fetched_contact['gsid'])) {
			$apcontact['gsid'] = $fetched_contact['gsid'];
		} else {
			$apcontact['gsid'] = null;
		}

		if ($apcontact['url'] == $apcontact['alias']) {
			$apcontact['alias'] = null;
		}

		$apcontact['updated'] = DateTimeFormat::utcNow();

		// We delete the old entry when the URL is changed
		if ($url != $apcontact['url']) {
			Logger::info('Delete changed profile url', ['old' => $url, 'new' => $apcontact['url']]);
			DBA::delete('apcontact', ['url' => $url]);
		}

		if (DBA::exists('apcontact', ['url' => $apcontact['url']])) {
			DBA::update('apcontact', $apcontact, ['url' => $apcontact['url']]);
		} else {
			DBA::replace('apcontact', $apcontact);
		}

		Logger::info('Updated profile', ['url' => $url]);

		return $apcontact;
	}

	/**
	 * Mark the given AP Contact as "to archive"
	 *
	 * @param array $apcontact
	 * @return void
	 */
	public static function markForArchival(array $apcontact)
	{
		if (!empty($apcontact['inbox'])) {
			Logger::info('Set inbox status to failure', ['inbox' => $apcontact['inbox']]);
			HTTPSignature::setInboxStatus($apcontact['inbox'], false);
		}

		if (!empty($apcontact['sharedinbox'])) {
			// Check if there are any available inboxes
			$available = DBA::exists('apcontact', ["`sharedinbox` = ? AnD `inbox` IN (SELECT `url` FROM `inbox-status` WHERE `success` > `failure`)",
				$apcontact['sharedinbox']]);
			if (!$available) {
				// If all known personal inboxes are failing then set their shared inbox to failure as well
				Logger::info('Set shared inbox status to failure', ['sharedinbox' => $apcontact['sharedinbox']]);
				HTTPSignature::setInboxStatus($apcontact['sharedinbox'], false, true);
			}
		}
	}

	/**
	 * Unmark the given AP Contact as "to archive"
	 *
	 * @param array $apcontact
	 * @return void
	 */
	public static function unmarkForArchival(array $apcontact)
	{
		if (!empty($apcontact['inbox'])) {
			Logger::info('Set inbox status to success', ['inbox' => $apcontact['inbox']]);
			HTTPSignature::setInboxStatus($apcontact['inbox'], true);
		}
		if (!empty($apcontact['sharedinbox'])) {
			Logger::info('Set shared inbox status to success', ['sharedinbox' => $apcontact['sharedinbox']]);
			HTTPSignature::setInboxStatus($apcontact['sharedinbox'], true, true);
		}
	}

	/**
	 * Unarchive inboxes
	 *
	 * @param string  $url    inbox url
	 * @param boolean $shared Shared Inbox
	 */
	private static function unarchiveInbox($url, $shared)
	{
		if (empty($url)) {
			return;
		}

		HTTPSignature::setInboxStatus($url, true, $shared);
	}
}
