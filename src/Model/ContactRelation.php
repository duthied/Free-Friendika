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

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

class ContactRelation
{
	/**
	 * No discovery of followers/followings
	 */
	const DISCOVERY_NONE = 0;
	/**
	 * Discover followers/followings of local contacts
	 */
	const DISCOVERY_LOCAL = 1;
	/**
	 * Discover followers/followings of local contacts and contacts that visibly interacted on the system
	 */
	const DISCOVERY_INTERACTOR = 2;
	/**
	 * Discover followers/followings of all contacts
	 */
	const DISCOVERY_ALL = 3;

	public static function store(int $target, int $actor, string $interaction_date)
	{
		if ($actor == $target) {
			return;
		}

		DBA::update('contact-relation', ['last-interaction' => $interaction_date], ['cid' => $target, 'relation-cid' => $actor], true);
	}

	/**
	 * Fetches the followers of a given profile and adds them
	 *
	 * @param string $url URL of a profile
	 * @return void
	 */
	public static function discoverByUrl(string $url)
	{
		$contact_discovery = DI::config()->get('system', 'contact_discovery');

		if ($contact_discovery == self::DISCOVERY_NONE) {
			return;
		}

		$contact = Contact::getByURL($url);
		if (empty($contact)) {
			return;
		}

		if ($contact['last-discovery'] > DateTimeFormat::utc('now - 1 month')) {
			Logger::info('Last discovery was less then a month before.', ['id' => $contact['id'], 'url' => $url, 'discovery' => $contact['last-discovery']]);
			return;
		}

		if ($contact_discovery != self::DISCOVERY_ALL) {
			$local = DBA::exists('contact', ["`nurl` = ? AND `uid` != ?", Strings::normaliseLink($url), 0]);
			if (($contact_discovery == self::DISCOVERY_LOCAL) && !$local) {
				Logger::info('No discovery - This contact is not followed/following locally.', ['id' => $contact['id'], 'url' => $url]);
				return;
			}

			if ($contact_discovery == self::DISCOVERY_INTERACTOR) {
				$interactor = DBA::exists('contact-relation', ["`relation-cid` = ? AND `last-interaction` > ?", $contact['id'], DBA::NULL_DATETIME]);
				if (!$local && !$interactor) {
					Logger::info('No discovery - This contact is not interacting locally.', ['id' => $contact['id'], 'url' => $url]);
					return;
				}
			}
		} elseif ($contact['created'] > DateTimeFormat::utc('now - 1 day')) {
			Logger::info('Newly created contacs are not discovered to avoid DDoS attacks.', ['id' => $contact['id'], 'url' => $url, 'discovery' => $contact['created']]);
			return;
		}

		$apcontact = APContact::getByURL($url);

		if (!empty($apcontact['followers']) && is_string($apcontact['followers'])) {
			$followers = ActivityPub::fetchItems($apcontact['followers']);
		} else {
			$followers = [];
		}

		if (!empty($apcontact['following']) && is_string($apcontact['following'])) {
			$followings = ActivityPub::fetchItems($apcontact['following']);
		} else {
			$followings = [];
		}

		if (empty($followers) && empty($followings)) {
			return;
		}

		$target = $contact['id'];

		if (!empty($followers)) {
			// Clear the follower list, since it will be recreated in the next step
			DBA::update('contact-relation', ['follows' => false], ['cid' => $target]);
		}

		$contacts = [];
		foreach (array_merge($followers, $followings) as $contact) {
			if (is_string($contact)) {
				$contacts[] = $contact;
			} elseif (!empty($contact['url']) && is_string($contact['url'])) {
				$contacts[] = $contact['url'];
			}
		}
		$contacts = array_unique($contacts);

		Logger::info('Discover contacts', ['id' => $target, 'url' => $url, 'contacts' => count($contacts)]);
		foreach ($contacts as $contact) {
			$actor = Contact::getIdForURL($contact);
			if (!empty($actor)) {
				$fields = [];
				if (in_array($contact, $followers)) {
					$fields = ['cid' => $target, 'relation-cid' => $actor];
				} elseif (in_array($contact, $followings)) {
					$fields = ['cid' => $actor, 'relation-cid' => $target];
				} else {
					continue;
				}

				DBA::update('contact-relation', ['follows' => true, 'follow-updated' => DateTimeFormat::utcNow()], $fields, true);
			}
		}

		if (!empty($followers)) {
			// Delete all followers that aren't followers anymore (and aren't interacting)
			DBA::delete('contact-relation', ['cid' => $target, 'follows' => false, 'last-interaction' => DBA::NULL_DATETIME]);
		}

		DBA::update('contact', ['last-discovery' => DateTimeFormat::utcNow()], ['id' => $target]);
		Logger::info('Contacts discovery finished, "last-discovery" set', ['id' => $target, 'url' => $url]);
		return;
	}
}
