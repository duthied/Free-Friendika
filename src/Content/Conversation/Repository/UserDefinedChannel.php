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
use Friendica\Database\Database;
use Psr\Log\LoggerInterface;

class UserDefinedChannel extends \Friendica\BaseRepository
{
	protected static $table_name = 'channel';

	public function __construct(Database $database, LoggerInterface $logger, Factory\UserDefinedChannel $factory)
	{
		parent::__construct($database, $logger, $factory);
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
		return $this->db->delete('channel', ['id' => $id, 'uid' => $uid]);
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
}
