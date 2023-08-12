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

namespace Friendica\User\Settings\Repository;

use Exception;
use Friendica\BaseCollection;
use Friendica\BaseEntity;
use Friendica\Content\Pager;
use Friendica\Database\Database;
use Friendica\Federation\Repository\GServer;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\User\Settings\Collection;
use Friendica\User\Settings\Entity;
use Friendica\User\Settings\Factory;
use Psr\Log\LoggerInterface;

class UserGServer extends \Friendica\BaseRepository
{
	protected static $table_name = 'user-gserver';

	/** @var Factory\UserGServer */
	protected $factory;
	/** @var GServer */
	protected $gserverRepository;

	public function __construct(GServer $gserverRepository, Database $database, LoggerInterface $logger, Factory\UserGServer $factory)
	{
		parent::__construct($database, $logger, $factory);

		$this->gserverRepository = $gserverRepository;
	}

	/**
	 * Returns an existing UserGServer entity or create one on the fly
	 *
	 * @param int  $uid
	 * @param int  $gsid
	 * @param bool $hydrate Populate the related GServer entity
	 * @return Entity\UserGServer
	 */
	public function getOneByUserAndServer(int $uid, int $gsid, bool $hydrate = true): Entity\UserGServer
	{
		try {
			return $this->selectOneByUserAndServer($uid, $gsid, $hydrate);
		} catch (NotFoundException $e) {
			return $this->factory->createFromUserAndServer($uid, $gsid, $hydrate ? $this->gserverRepository->selectOneById($gsid) : null);
		}
	}

	/**
	 * @param int  $uid
	 * @param int  $gsid
	 * @param bool $hydrate Populate the related GServer entity
	 * @return Entity\UserGServer
	 * @throws NotFoundException
	 */
	public function selectOneByUserAndServer(int $uid, int $gsid, bool $hydrate = true): Entity\UserGServer
	{
		return $this->_selectOne(['uid' => $uid, 'gsid' => $gsid], [], $hydrate);
	}

	public function save(Entity\UserGServer $userGServer): Entity\UserGServer
	{
		$fields = [
			'uid'     => $userGServer->uid,
			'gsid'    => $userGServer->gsid,
			'ignored' => $userGServer->ignored,
		];

		$this->db->insert(static::$table_name, $fields, Database::INSERT_UPDATE);

		return $userGServer;
	}

	public function selectByUserWithPagination(int $uid, Pager $pager): Collection\UserGServers
	{
		return $this->_select(['uid' => $uid], ['limit' => [$pager->getStart(), $pager->getItemsPerPage()]]);
	}

	public function countByUser(int $uid): int
	{
		return $this->count(['uid' => $uid]);
	}

	public function isIgnoredByUser(int $uid, int $gsid): bool
	{
		return $this->exists(['uid' => $uid, 'gsid' => $gsid, 'ignored' => 1]);
	}

	/**
	 * @param Entity\UserGServer $userGServer
	 * @return bool
	 * @throws InternalServerErrorException in case the underlying storage cannot delete the record
	 */
	public function delete(Entity\UserGServer $userGServer): bool
	{
		try {
			return $this->db->delete(self::$table_name, ['uid' => $userGServer->uid, 'gsid' => $userGServer->gsid]);
		} catch (\Exception $exception) {
			throw new InternalServerErrorException('Cannot delete the UserGServer', $exception);
		}
	}

	protected function _selectOne(array $condition, array $params = [], bool $hydrate = true): BaseEntity
	{
		$fields = $this->db->selectFirst(static::$table_name, [], $condition, $params);
		if (!$this->db->isResult($fields)) {
			throw new NotFoundException();
		}

		return $this->factory->createFromTableRow($fields, $hydrate ? $this->gserverRepository->selectOneById($fields['gsid']) : null);
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @return Collection\UserGServers
	 * @throws Exception
	 */
	protected function _select(array $condition, array $params = [], bool $hydrate = true): BaseCollection
	{
		$rows = $this->db->selectToArray(static::$table_name, [], $condition, $params);

		$Entities = new Collection\UserGServers();
		foreach ($rows as $fields) {
			$Entities[] = $this->factory->createFromTableRow($fields, $hydrate ? $this->gserverRepository->selectOneById($fields['gsid']) : null);
		}

		return $Entities;
	}

	public function listIgnoredByUser(int $uid): Collection\UserGServers
	{
		return $this->_select(['uid' => $uid, 'ignored' => 1], [], false);
	}
}
