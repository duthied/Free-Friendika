<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Content\Conversation\Repository;

use Friendica\BaseCollection;
use Friendica\Content\Conversation\Collection\UserDefinedChannels;
use Friendica\Content\Conversation\Entity;
use Friendica\Content\Conversation\Factory;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Post\Engagement;
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

class UserDefinedChannel extends \Friendica\BaseRepository
{
	protected static $table_name = 'channel';

	/** @var IManageConfigValues */
	private $config;

	public function __construct(Database $database, LoggerInterface $logger, Factory\UserDefinedChannel $factory, IManageConfigValues $config)
	{
		parent::__construct($database, $logger, $factory);

		$this->config = $config;
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @return UserDefinedChannels
	 * @throws \Exception
	 */
	protected function _select(array $condition, array $params = []): BaseCollection
	{
		$rows = $this->db->selectToArray(static::$table_name, [], $condition, $params);

		$Entities = new UserDefinedChannels();
		foreach ($rows as $fields) {
			$Entities[] = $this->factory->createFromTableRow($fields);
		}

		return $Entities;
	}

	public function select(array $condition, array $params = []): BaseCollection
	{
		return $this->_select($condition, $params);
	}

	/**
	 * Fetch a single user channel
	 *
	 * @param int $id  The id of the user defined channel
	 * @param int $uid The user that this channel belongs to. (Not part of the primary key)
	 * @return Entity\UserDefinedChannel
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 */
	public function selectById(int $id, int $uid): Entity\UserDefinedChannel
	{
		return $this->_selectOne(['id' => $id, 'uid' => $uid]);
	}

	/**
	 * Checks if the provided channel id exists for this user
	 *
	 * @param integer $id
	 * @param integer $uid
	 * @return boolean
	 */
	public function existsById(int $id, int $uid): bool
	{
		return $this->exists(['id' => $id, 'uid' => $uid]);
	}

	/**
	 * Delete the given channel
	 *
	 * @param integer $id
	 * @param integer $uid
	 * @return boolean
	 */
	public function deleteById(int $id, int $uid): bool
	{
		return $this->db->delete(self::$table_name, ['id' => $id, 'uid' => $uid]);
	}

	/**
	 * Fetch all user channels
	 *
	 * @param integer $uid
	 * @return UserDefinedChannels
	 * @throws \Exception
	 */
	public function selectByUid(int $uid): UserDefinedChannels
	{
		return $this->_select(['uid' => $uid]);
	}

	public function save(Entity\UserDefinedChannel $Channel): Entity\UserDefinedChannel
	{
		$fields = [
			'label'            => $Channel->label,
			'description'      => $Channel->description,
			'access-key'       => $Channel->accessKey,
			'uid'              => $Channel->uid,
			'circle'           => $Channel->circle,
			'include-tags'     => $Channel->includeTags,
			'exclude-tags'     => $Channel->excludeTags,
			'full-text-search' => $Channel->fullTextSearch,
			'media-type'       => $Channel->mediaType,
			'languages'        => serialize($Channel->languages),
		];

		if ($Channel->code) {
			$this->db->update(self::$table_name, $fields, ['uid' => $Channel->uid, 'id' => $Channel->code]);
		} else {
			$this->db->insert(self::$table_name, $fields, Database::INSERT_IGNORE);

			$newChannelId = $this->db->lastInsertId();

			$Channel = $this->selectById($newChannelId, $Channel->uid);
		}

		return $Channel;
	}

	/**
	 * Checks, if one of the user defined channels matches with the given search text
	 * @todo To increase the performance, this functionality should be replaced with a single SQL call.
	 *
	 * @param string $searchtext
	 * @param string $language
	 * @param array  $tags
	 * @param int    $media_type
	 * @return boolean
	 */
	public function match(string $searchtext, string $language, array $tags, int $media_type): bool
	{
		$condition = ["`verified` AND NOT `blocked` AND NOT `account_removed` AND NOT `account_expired` AND `user`.`uid` > ?", 0];

		$abandon_days = intval($this->config->get('system', 'account_abandon_days'));
		if (!empty($abandon_days)) {
			$condition = DBA::mergeConditions($condition, ["`last-activity` > ?", DateTimeFormat::utc('now - ' . $abandon_days . ' days')]);
		}

		$users = $this->db->selectToArray('user', ['uid'], $condition);
		if (empty($users)) {
			return [];
		}

		return !empty($this->getMatches($searchtext, $language, $tags, $media_type, 0, array_column($users, 'uid'), false));
	}

	/**
	 * Fetch the channel users that have got matching channels
	 *
	 * @param string $searchtext
	 * @param string $language
	 * @param array $tags
	 * @param integer $media_type
	 * @return array
	 */
	public function getMatchingChannelUsers(string $searchtext, string $language, array $tags, int $media_type, int $author_id): array
	{
		$users = $this->db->selectToArray('user', ['uid'], ["`account-type` = ? AND `uid` != ?", User::ACCOUNT_TYPE_RELAY, 0]);
		if (empty($users)) {
			return [];
		}
		return $this->getMatches($searchtext, $language, $tags, $media_type, $author_id, array_column($users, 'uid'), true);
	}

	private function getMatches(string $searchtext, string $language, array $tags, int $media_type, int $author_id, array $channelUids, bool $relayMode): array
	{
		if (!in_array($language, User::getLanguages())) {
			$this->logger->debug('Unwanted language found. No matched channel found.', ['language' => $language, 'searchtext' => $searchtext]);
			return [];
		}

		$this->db->insert('check-full-text-search', ['pid' => getmypid(), 'searchtext' => $searchtext], Database::INSERT_UPDATE);

		$uids = [];

		$condition = ['uid' => $channelUids];
		if (!$relayMode) {
			$condition = DBA::mergeConditions($condition, ["`full-text-search` != ?", '']);
		}

		foreach ($this->select($condition) as $channel) {
			if (in_array($channel->uid, $uids)) {
				continue;
			}
			if (!empty($channel->circle) && ($channel->circle > 0) && !in_array($channel->uid, $uids)) {
				$account = Contact::selectFirstAccountUser(['id'], ['pid' => $author_id, 'uid' => $channel->uid]);
				if (empty($account['id']) || !$this->db->exists('group_member', ['gid' => $channel->circle, 'contact-id' => $account['id']])) {
					continue;
				}
			}
			if (!empty($channel->languages) && !in_array($channel->uid, $uids)) {
				if (!in_array($language, $channel->languages)) {
					continue;
				}
			} elseif (!in_array($language, User::getWantedLanguages($channel->uid))) {
				continue;
			}
			if (!empty($channel->includeTags) && !in_array($channel->uid, $uids)) {
				if (empty($tags)) {
					continue;
				}
				$match = false;
				foreach (explode(',', $channel->includeTags) as $tag) {
					if (in_array($tag, $tags)) {
						$match = true;
						break;
					}
				}
				if (!$match) {
					continue;
				}
			}
			if (!empty($tags) && !empty($channel->excludeTags) && !in_array($channel->uid, $uids)) {
				$match = false;
				foreach (explode(',', $channel->excludeTags) as $tag) {
					if (in_array($tag, $tags)) {
						$match = true;
						break;
					}
				}
				if ($match) {
					continue;
				}
			}
			if (!empty($channel->mediaType) && !in_array($channel->uid, $uids)) {
				if (!($channel->mediaType & $media_type)) {
					continue;
				}
			}
			if (!empty($channel->fullTextSearch) && !in_array($channel->uid, $uids)) {
				$channelsearchtext = $channel->fullTextSearch;
				foreach (Engagement::KEYWORDS as $keyword) {
					$channelsearchtext = preg_replace('~(' . $keyword . ':.[\w@\.-]+)~', '"$1"', $channelsearchtext);
				}
				if (!$this->db->exists('check-full-text-search', ["`pid` = ? AND MATCH (`searchtext`) AGAINST (? IN BOOLEAN MODE)", getmypid(), $channelsearchtext])) {
					continue;
				}
			}
			$uids[] = $channel->uid;
			$this->logger->debug('Matching channel found.', ['uid' => $channel->uid, 'label' => $channel->label, 'language' => $language, 'tags' => $tags, 'media_type' => $media_type, 'searchtext' => $searchtext]);
			if (!$relayMode) {
				return $uids;
			}
		}

		$this->db->delete('check-full-text-search', ['pid' => getmypid()]);
		return $uids;
	}
}
