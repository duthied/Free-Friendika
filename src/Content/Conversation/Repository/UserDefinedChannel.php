<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Database\Database;
use Friendica\Model\Post\Engagement;
use Friendica\Model\User;
use Psr\Log\LoggerInterface;

class UserDefinedChannel extends \Friendica\BaseRepository
{
	protected static $table_name = 'channel';

	/** @var IManagePersonalConfigValues */
	private $pConfig;

	public function __construct(Database $database, LoggerInterface $logger, Factory\UserDefinedChannel $factory, IManagePersonalConfigValues $pConfig)
	{
		parent::__construct($database, $logger, $factory);

		$this->pConfig = $pConfig;
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
	 * @return boolean
	 */
	public function match(string $searchtext, string $language): bool
	{
		if (!in_array($language, User::getLanguages())) {
			$this->logger->debug('Unwanted language found. No matched channel found.', ['language' => $language, 'searchtext' => $searchtext]);
			return false;
		}

		$store = false;
		$this->db->insert('check-full-text-search', ['pid' => getmypid(), 'searchtext' => $searchtext], Database::INSERT_UPDATE);
		$channels = $this->db->select(self::$table_name, ['full-text-search', 'uid', 'label'], ["`full-text-search` != ? AND `circle` = ?", '', 0]);
		while ($channel = $this->db->fetch($channels)) {
			$channelsearchtext = $channel['full-text-search'];
			foreach (Engagement::KEYWORDS as $keyword) {
				$channelsearchtext = preg_replace('~(' . $keyword . ':.[\w@\.-]+)~', '"$1"', $channelsearchtext);
			}
			if ($this->db->exists('check-full-text-search', ["`pid` = ? AND MATCH (`searchtext`) AGAINST (? IN BOOLEAN MODE)", getmypid(), $channelsearchtext])) {
				if (in_array($language, $this->pConfig->get($channel['uid'], 'channel', 'languages', [User::getLanguageCode($channel['uid'])]))) {
					$store = true;
					$this->logger->debug('Matching channel found.', ['uid' => $channel['uid'], 'label' => $channel['label'], 'language' => $language, 'channelsearchtext' => $channelsearchtext, 'searchtext' => $searchtext]);
					break;
				}
			}
		}
		$this->db->close($channels);

		$this->db->delete('check-full-text-search', ['pid' => getmypid()]);
		return $store;
	}
}
