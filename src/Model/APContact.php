<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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
use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Network\Probe;
use Friendica\Protocol\ActivityNamespace;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\Crypto;
use Friendica\Util\Network;
use Friendica\Util\JsonLD;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

class APContact
{
	/**
	 * Fetch webfinger data
	 *
	 * @param string $addr Address
	 * @return array webfinger data
	 */
	public static function fetchWebfingerData(string $addr)
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

		$data = ActivityPub::fetchContent($url);
		if (empty($data)) {
			return $fetched_contact;
		}

		$compacted = JsonLD::compact($data);

		if (empty($compacted['@id'])) {
			return $fetched_contact;
		}

		$apcontact['url'] = $compacted['@id'];
		$apcontact['uuid'] = JsonLD::fetchElement($compacted, 'diaspora:guid', '@value');
		$apcontact['type'] = str_replace('as:', '', JsonLD::fetchElement($compacted, '@type'));
		$apcontact['following'] = JsonLD::fetchElement($compacted, 'as:following', '@id');
		$apcontact['followers'] = JsonLD::fetchElement($compacted, 'as:followers', '@id');
		$apcontact['inbox'] = JsonLD::fetchElement($compacted, 'ldp:inbox', '@id');
		self::unarchiveInbox($apcontact['inbox'], false);

		$apcontact['outbox'] = JsonLD::fetchElement($compacted, 'as:outbox', '@id');

		$apcontact['sharedinbox'] = '';
		if (!empty($compacted['as:endpoints'])) {
			$apcontact['sharedinbox'] = JsonLD::fetchElement($compacted['as:endpoints'], 'as:sharedInbox', '@id');
			self::unarchiveInbox($apcontact['sharedinbox'], true);
		}

		$apcontact['nick'] = JsonLD::fetchElement($compacted, 'as:preferredUsername', '@value') ?? '';
		$apcontact['name'] = JsonLD::fetchElement($compacted, 'as:name', '@value');

		if (empty($apcontact['name'])) {
			$apcontact['name'] = $apcontact['nick'];
		}

		$apcontact['about'] = HTML::toBBCode(JsonLD::fetchElement($compacted, 'as:summary', '@value'));

		$apcontact['photo'] = JsonLD::fetchElement($compacted, 'as:icon', '@id');
		if (is_array($apcontact['photo']) || !empty($compacted['as:icon']['as:url']['@id'])) {
			$apcontact['photo'] = JsonLD::fetchElement($compacted['as:icon'], 'as:url', '@id');
		}

		if (empty($apcontact['alias'])) {
			$apcontact['alias'] = JsonLD::fetchElement($compacted, 'as:url', '@id');
			if (is_array($apcontact['alias'])) {
				$apcontact['alias'] = JsonLD::fetchElement($compacted['as:url'], 'as:href', '@id');
			}
		}

		// Quit if none of the basic values are set
		if (empty($apcontact['url']) || empty($apcontact['inbox']) || empty($apcontact['type'])) {
			return $fetched_contact;
		}

		// Quit if this doesn't seem to be an account at all
		if (!in_array($apcontact['type'], ActivityPub::ACCOUNT_TYPES)) {
			return $fetched_contact;
		}

		$parts = parse_url($apcontact['url']);
		unset($parts['scheme']);
		unset($parts['path']);

		if (empty($apcontact['addr'])) {
			if (!empty($apcontact['nick'])) {
				$apcontact['addr'] = $apcontact['nick'] . '@' . str_replace('//', '', Network::unparseURL($parts));
			} else {
				$apcontact['addr'] = '';
			}
		}

		$apcontact['pubkey'] = null;
		if (!empty($compacted['w3id:publicKey'])) {
			$apcontact['pubkey'] = trim(JsonLD::fetchElement($compacted['w3id:publicKey'], 'w3id:publicKeyPem', '@value'));
			if (strstr($apcontact['pubkey'], 'RSA ')) {
				$apcontact['pubkey'] = Crypto::rsaToPem($apcontact['pubkey']);
			}
		}

		$apcontact['manually-approve'] = (int)JsonLD::fetchElement($compacted, 'as:manuallyApprovesFollowers');

		if (!empty($compacted['as:generator'])) {
			$apcontact['baseurl'] = JsonLD::fetchElement($compacted['as:generator'], 'as:url', '@id');
			$apcontact['generator'] = JsonLD::fetchElement($compacted['as:generator'], 'as:name', '@value');
		}

		if (!empty($apcontact['following'])) {
			$data = ActivityPub::fetchContent($apcontact['following']);
			if (!empty($data)) {
				if (!empty($data['totalItems'])) {
					$apcontact['following_count'] = $data['totalItems'];
				}
			}
		}

		if (!empty($apcontact['followers'])) {
			$data = ActivityPub::fetchContent($apcontact['followers']);
			if (!empty($data)) {
				if (!empty($data['totalItems'])) {
					$apcontact['followers_count'] = $data['totalItems'];
				}
			}
		}

		if (!empty($apcontact['outbox'])) {
			$data = ActivityPub::fetchContent($apcontact['outbox']);
			if (!empty($data)) {
				if (!empty($data['totalItems'])) {
					$apcontact['statuses_count'] = $data['totalItems'];
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

		DBA::update('apcontact', $apcontact, ['url' => $url], true);

		// We delete the old entry when the URL is changed
		if (($url != $apcontact['url']) && DBA::exists('apcontact', ['url' => $url]) && DBA::exists('apcontact', ['url' => $apcontact['url']])) {
			DBA::delete('apcontact', ['url' => $url]);
		}

		Logger::info('Updated profile', ['url' => $url]);

		return $apcontact;
	}

	/**
	 * Unarchive inboxes
	 *
	 * @param string $url inbox url
	 */
	private static function unarchiveInbox($url, $shared)
	{
		if (empty($url)) {
			return;
		}

		$now = DateTimeFormat::utcNow();

		$fields = ['archive' => false, 'success' => $now, 'shared' => $shared];

		if (!DBA::exists('inbox-status', ['url' => $url])) {
			$fields = array_merge($fields, ['url' => $url, 'created' => $now]);
			DBA::insert('inbox-status', $fields);
		} else {
			DBA::update('inbox-status', $fields, ['url' => $url]);
		}
	}
}
