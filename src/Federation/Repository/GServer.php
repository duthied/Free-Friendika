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

namespace Friendica\Federation\Repository;

use Friendica\Database\Database;
use Friendica\Federation\Factory;
use Friendica\Federation\Entity;
use Psr\Log\LoggerInterface;

class GServer extends \Friendica\BaseRepository
{
	protected static $table_name = 'gserver';

	public function __construct(Database $database, LoggerInterface $logger, Factory\GServer $factory)
	{
		parent::__construct($database, $logger, $factory);
	}

	/**
	 * @param int $gsid
	 * @return Entity\GServer
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 */
	public function selectOneById(int $gsid): Entity\GServer
	{
		return $this->_selectOne(['id' => $gsid]);
	}
}
