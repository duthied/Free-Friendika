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

use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;

/**
 * functions for interacting with a process
 */
class Process
{
	/**
	 * Insert a new process row. If the pid parameter is omitted, we use the current pid
	 *
	 * @param string $command
	 * @param string $pid
	 * @return bool
	 * @throws \Exception
	 */
	public static function insert($command, $pid = null)
	{
		$return = true;

		if (is_null($pid)) {
			$pid = getmypid();
		}

		DBA::transaction();

		if (!DBA::exists('process', ['pid' => $pid])) {
			$return = DBA::insert('process', ['pid' => $pid, 'command' => $command, 'created' => DateTimeFormat::utcNow()]);
		}

		DBA::commit();

		return $return;
	}

	/**
	 * Remove a process row by pid. If the pid parameter is omitted, we use the current pid
	 *
	 * @param string $pid
	 * @return bool
	 * @throws \Exception
	 */
	public static function deleteByPid($pid = null)
	{
		if ($pid === null) {
			$pid = getmypid();
		}

		return DBA::delete('process', ['pid' => $pid]);
	}

	/**
	 * Clean the process table of inactive physical processes
	 */
	public static function deleteInactive()
	{
		DBA::transaction();

		$processes = DBA::select('process', ['pid']);
		while($process = DBA::fetch($processes)) {
			if (!posix_kill($process['pid'], 0)) {
				self::deleteByPid($process['pid']);
			}
		}
		DBA::close($processes);
		DBA::commit();
	}
}
