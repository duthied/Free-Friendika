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

use Friendica\Database\Database;
use Friendica\Util\DateTimeFormat;

/**
 * functions for interacting with a process
 */
class Process
{
	/** @var Database */
	private $dba;

	public function __construct(Database $dba)
	{
		$this->dba = $dba;
	}

	/**
	 * Insert a new process row. If the pid parameter is omitted, we use the current pid
	 *
	 * @param string $command
	 * @param int $pid The process id to insert
	 * @return bool
	 * @throws \Exception
	 */
	public function insert(string $command, int $pid)
	{
		$return = true;

		$this->dba->transaction();

		if (!$this->dba->exists('process', ['pid' => $pid])) {
			$return = $this->dba->insert('process', ['pid' => $pid, 'command' => $command, 'created' => DateTimeFormat::utcNow()]);
		}

		$this->dba->commit();

		return $return;
	}

	/**
	 * Remove a process row by pid. If the pid parameter is omitted, we use the current pid
	 *
	 * @param int $pid The pid to delete
	 * @return bool
	 * @throws \Exception
	 */
	public function deleteByPid(int $pid)
	{
		return $this->dba->delete('process', ['pid' => $pid]);
	}

	/**
	 * Clean the process table of inactive physical processes
	 */
	public function deleteInactive()
	{
		$this->dba->transaction();

		$processes = $this->dba->select('process', ['pid']);
		while($process = $this->dba->fetch($processes)) {
			if (!posix_kill($process['pid'], 0)) {
				$this->deleteByPid($process['pid']);
			}
		}
		$this->dba->close($processes);
		$this->dba->commit();
	}
}
