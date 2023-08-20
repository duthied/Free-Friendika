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

namespace Friendica\User\Settings\Factory;

use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Federation\Entity\GServer;
use Friendica\User\Settings\Entity;

class UserGServer extends \Friendica\BaseFactory implements ICanCreateFromTableRow
{
	/**
	 * @param array        $row    `user-gserver` table row
	 * @param GServer|null $server Corresponding GServer entity
	 * @return Entity\UserGServer
	 */
	public function createFromTableRow(array $row, GServer $server = null): Entity\UserGServer
	{
		return new Entity\UserGServer(
			$row['uid'],
			$row['gsid'],
			$row['ignored'],
			$server,
		);
	}

	/**
	 * @param int          $uid
	 * @param int          $gsid
	 * @param GServer|null $gserver Corresponding GServer entity
	 * @return Entity\UserGServer
	 */
	public function createFromUserAndServer(int $uid, int $gsid, GServer $gserver = null): Entity\UserGServer
	{
		return new Entity\UserGServer(
			$uid,
			$gsid,
			false,
			$gserver,
		);
	}
}
