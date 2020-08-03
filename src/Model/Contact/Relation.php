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

namespace Friendica\Model\Contact;

use Exception;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

/**
 * This class provides relationship information based on the `contact-relation` table.
 * This table is directional (cid = source, relation-cid = target), references public contacts (with uid=0) and records both
 * follows and the last interaction (likes/comments) on public posts.
 */
class Relation
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
		$contact = Contact::getByURL($url);
		if (empty($contact)) {
			return;
		}

		if (!self::isDiscoverable($url, $contact)) {
			return;
		}

		$apcontact = APContact::getByURL($url, false);

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
			DBA::update('contact', ['last-discovery' => DateTimeFormat::utcNow()], ['id' => $contact['id']]);
			Logger::info('The contact does not offer discoverable data', ['id' => $contact['id'], 'url' => $url, 'network' => $contact['network']]);
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

		$follower_counter = 0;
		$following_counter = 0;

		Logger::info('Discover contacts', ['id' => $target, 'url' => $url, 'contacts' => count($contacts)]);
		foreach ($contacts as $contact) {
			$actor = Contact::getIdForURL($contact);
			if (!empty($actor)) {
				if (in_array($contact, $followers)) {
					$fields = ['cid' => $target, 'relation-cid' => $actor];
					DBA::update('contact-relation', ['follows' => true, 'follow-updated' => DateTimeFormat::utcNow()], $fields, true);
					$follower_counter++;
				}

				if (in_array($contact, $followings)) {
					$fields = ['cid' => $actor, 'relation-cid' => $target];
					DBA::update('contact-relation', ['follows' => true, 'follow-updated' => DateTimeFormat::utcNow()], $fields, true);
					$following_counter++;
				}
			}
		}

		if (!empty($followers)) {
			// Delete all followers that aren't followers anymore (and aren't interacting)
			DBA::delete('contact-relation', ['cid' => $target, 'follows' => false, 'last-interaction' => DBA::NULL_DATETIME]);
		}

		DBA::update('contact', ['last-discovery' => DateTimeFormat::utcNow()], ['id' => $target]);
		Logger::info('Contacts discovery finished', ['id' => $target, 'url' => $url, 'follower' => $follower_counter, 'following' => $following_counter]);
		return;
	}

	/**
	 * Tests if a given contact url is discoverable
	 *
	 * @param string $url     Contact url
	 * @param array  $contact Contact array
	 * @return boolean True if contact is discoverable
	 */
	public static function isDiscoverable(string $url, array $contact = [])
	{
		$contact_discovery = DI::config()->get('system', 'contact_discovery');

		if ($contact_discovery == self::DISCOVERY_NONE) {
			return false;
		}

		if (empty($contact)) {
			$contact = Contact::getByURL($url);
		}

		if (empty($contact)) {
			return false;
		}

		if ($contact['last-discovery'] > DateTimeFormat::utc('now - 1 month')) {
			Logger::info('No discovery - Last was less than a month ago.', ['id' => $contact['id'], 'url' => $url, 'discovery' => $contact['last-discovery']]);
			return false;
		}

		if ($contact_discovery != self::DISCOVERY_ALL) {
			$local = DBA::exists('contact', ["`nurl` = ? AND `uid` != ?", Strings::normaliseLink($url), 0]);
			if (($contact_discovery == self::DISCOVERY_LOCAL) && !$local) {
				Logger::info('No discovery - This contact is not followed/following locally.', ['id' => $contact['id'], 'url' => $url]);
				return false;
			}

			if ($contact_discovery == self::DISCOVERY_INTERACTOR) {
				$interactor = DBA::exists('contact-relation', ["`relation-cid` = ? AND `last-interaction` > ?", $contact['id'], DBA::NULL_DATETIME]);
				if (!$local && !$interactor) {
					Logger::info('No discovery - This contact is not interacting locally.', ['id' => $contact['id'], 'url' => $url]);
					return false;
				}
			}
		} elseif ($contact['created'] > DateTimeFormat::utc('now - 1 day')) {
			// Newly created contacts are not discovered to avoid DDoS attacks
			Logger::info('No discovery - Contact record is less than a day old.', ['id' => $contact['id'], 'url' => $url, 'discovery' => $contact['created']]);
			return false;
		}

		if (!in_array($contact['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::OSTATUS])) {
			$apcontact = APContact::getByURL($url, false);
			if (empty($apcontact)) {
				Logger::info('No discovery - The contact does not seem to speak ActivityPub.', ['id' => $contact['id'], 'url' => $url, 'network' => $contact['network']]);
				return false;
			}
		}

		return true;
	}

	/**
	 * Counts all the known follows of the provided public contact
	 *
	 * @param int   $cid       Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @return int
	 * @throws Exception
	 */
	public static function countFollows(int $cid, array $condition = [])
	{
		$condition = DBA::mergeConditions($condition,
			['`id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`)', 
			$cid]
		);

		return DI::dba()->count('contact', $condition);
	}

	/**
	 * Returns a paginated list of contacts that are followed the provided public contact.
	 *
	 * @param int   $cid       Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @param int   $count
	 * @param int   $offset
	 * @param bool  $shuffle
	 * @return array
	 * @throws Exception
	 */
	public static function listFollows(int $cid, array $condition = [], int $count = 30, int $offset = 0, bool $shuffle = false)
	{
		$condition = DBA::mergeConditions($condition,
			['`id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`)', 
			$cid]
		);

		return DI::dba()->selectToArray('contact', [], $condition,
			['limit' => [$offset, $count], 'order' => [$shuffle ? 'RAND()' : 'name']]
		);
	}

	/**
	 * Counts all the known followers of the provided public contact
	 *
	 * @param int   $cid       Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @return int
	 * @throws Exception
	 */
	public static function countFollowers(int $cid, array $condition = [])
	{
		$condition = DBA::mergeConditions($condition,
			['`id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `follows`)',
			$cid]
		);

		return DI::dba()->count('contact', $condition);
	}

	/**
	 * Returns a paginated list of contacts that follow the provided public contact.
	 *
	 * @param int   $cid       Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @param int   $count
	 * @param int   $offset
	 * @param bool  $shuffle
	 * @return array
	 * @throws Exception
	 */
	public static function listFollowers(int $cid, array $condition = [], int $count = 30, int $offset = 0, bool $shuffle = false)
	{
		$condition = DBA::mergeConditions($condition,
			['`id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `follows`)', $cid]
		);

		$followers = DI::dba()->selectToArray('contact', [], $condition,
			['limit' => [$offset, $count], 'order' => [$shuffle ? 'RAND()' : 'name']]
		);

		return $followers;
	}

	/**
	 * Counts the number of contacts that both provided public contacts have interacted with at least once.
	 * Interactions include follows and likes and comments on public posts.
	 *
	 * @param int   $sourceId  Public contact id
	 * @param int   $targetId  Public contact id
	 * @param array $condition Additional condition array on the contact table
	 * @return int
	 * @throws Exception
	 */
	public static function countCommon(int $sourceId, int $targetId, array $condition = [])
	{
		$condition = DBA::mergeConditions($condition,
			['`id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ?) 
			AND `id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ?)',
			$sourceId, $targetId]
		);

		$total = DI::dba()->count('contact', $condition);

		return $total;
	}

	/**
	 * Returns a paginated list of contacts that both provided public contacts have interacted with at least once.
	 * Interactions include follows and likes and comments on public posts.
	 *
	 * @param int   $sourceId  Public contact id
	 * @param int   $targetId  Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @param int   $count
	 * @param int   $offset
	 * @param bool  $shuffle
	 * @return array
	 * @throws Exception
	 */
	public static function listCommon(int $sourceId, int $targetId, array $condition = [], int $count = 30, int $offset = 0, bool $shuffle = false)
	{
		$condition = DBA::mergeConditions($condition,
			["`id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`) 
			AND `id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`)",
			$sourceId, $targetId]
		);

		$contacts = DI::dba()->selectToArray('contact', [], $condition,
			['limit' => [$offset, $count], 'order' => [$shuffle ? 'name' : 'RAND()']]
		);

		return $contacts;
	}


	/**
	 * Counts the number of contacts that are followed by both provided public contacts.
	 *
	 * @param int   $sourceId  Public contact id
	 * @param int   $targetId  Public contact id
	 * @param array $condition Additional condition array on the contact table
	 * @return int
	 * @throws Exception
	 */
	public static function countCommonFollows(int $sourceId, int $targetId, array $condition = [])
	{
		$condition = DBA::mergeConditions($condition,
			['`id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`) 
			AND `id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`)',
			$sourceId, $targetId]
		);

		$total = DI::dba()->count('contact', $condition);

		return $total;
	}

	/**
	 * Returns a paginated list of contacts that are followed by both provided public contacts.
	 *
	 * @param int   $sourceId  Public contact id
	 * @param int   $targetId  Public contact id
	 * @param array $condition Additional condition array on the contact table
	 * @param int   $count
	 * @param int   $offset
	 * @param bool  $shuffle
	 * @return array
	 * @throws Exception
	 */
	public static function listCommonFollows(int $sourceId, int $targetId, array $condition = [], int $count = 30, int $offset = 0, bool $shuffle = false)
	{
		$condition = DBA::mergeConditions($condition,
			["`id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`) 
			AND `id` IN (SELECT `relation-cid` FROM `contact-relation` WHERE `cid` = ? AND `follows`)",
			$sourceId, $targetId]
		);

		$contacts = DI::dba()->selectToArray('contact', [], $condition,
			['limit' => [$offset, $count], 'order' => [$shuffle ? 'name' : 'RAND()']]
		);

		return $contacts;
	}

	/**
	 * Counts the number of contacts that follow both provided public contacts.
	 *
	 * @param int   $sourceId  Public contact id
	 * @param int   $targetId  Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @return int
	 * @throws Exception
	 */
	public static function countCommonFollowers(int $sourceId, int $targetId, array $condition = [])
	{
		$condition = DBA::mergeConditions($condition,
			["`id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `follows`) 
			AND `id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `follows`)",
			$sourceId, $targetId]
		);

		return DI::dba()->count('contact', $condition);
	}

	/**
	 * Returns a paginated list of contacts that follow both provided public contacts.
	 *
	 * @param int   $sourceId  Public contact id
	 * @param int   $targetId  Public contact id
	 * @param array $condition Additional condition on the contact table
	 * @param int   $count
	 * @param int   $offset
	 * @param bool  $shuffle
	 * @return array
	 * @throws Exception
	 */
	public static function listCommonFollowers(int $sourceId, int $targetId, array $condition = [], int $count = 30, int $offset = 0, bool $shuffle = false)
	{
		$condition = DBA::mergeConditions($condition,
			["`id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `follows`) 
			AND `id` IN (SELECT `cid` FROM `contact-relation` WHERE `relation-cid` = ? AND `follows`)",
			$sourceId, $targetId]
		);

		return DI::dba()->selectToArray('contact', [], $condition,
			['limit' => [$offset, $count], 	'order' => [$shuffle ? 'name' : 'RAND()']]
		);
	}
}
