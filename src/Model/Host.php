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

class Host
{
	/**
	 * Get the id for a given hostname
	 * When empty, the current hostname is used
	 *
	 * @param string $hostname
	 *
	 * @return integer host name id
	 * @throws \Exception
	 */
	public static function getId(string $hostname = '')
	{
		if (empty($hostname)) {
			$hostname = php_uname('n');
		}

		$hostname = strtolower($hostname);

		$host = DBA::selectFirst('host', ['id'], ['name' => $hostname]);
		if (!empty($host['id'])) {
			return $host['id'];
		}

		DBA::replace('host', ['name' => $hostname]);

		$host = DBA::selectFirst('host', ['id'], ['name' => $hostname]);
		if (empty($host['id'])) {
			Logger::warning('Host name could not be inserted', ['name' => $hostname]);
			return 0;
		}

		return $host['id'];
	}

	/**
	 * Get the hostname for a given id
	 *
	 * @param int $id
	 *
	 * @return string host name
	 * @throws \Exception
	 */
	public static function getName(int $id)
	{
		$host = DBA::selectFirst('host', ['name'], ['id' => $id]);
		if (!empty($host['name'])) {
			return $host['name'];
		}

		return '';
	}
}
