<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Core\Worker\Repository;

use Friendica\BaseRepository;
use Friendica\Core\Worker\Exception\ProcessPersistenceException;
use Friendica\Database\Database;
use Friendica\Util\DateTimeFormat;
use Friendica\Core\Worker\Factory;
use Friendica\Core\Worker\Entity;
use Psr\Log\LoggerInterface;

/**
 * functions for interacting with a process
 */
class Process extends BaseRepository
{
	protected static $table_name = 'process';

	/** @var Factory\Process */
	protected $factory;

	public function __construct(Database $database, LoggerInterface $logger, Factory\Process $factory)
	{
		parent::__construct($database, $logger, $factory);
	}

	/**
	 * Starts and Returns the process for a given PID
	 *
	 * @param int $pid
	 *
	 * @return Entity\Process
	 */
	public function create(int $pid): Entity\Process
	{
		// Cleanup inactive process
		$this->deleteInactive();

		try {
			$this->db->transaction();

			$newProcess = $this->factory->create($pid);

			if (!$this->db->exists('process', ['pid' => $pid])) {
				if (!$this->db->insert(static::$table_name, [
					'pid' => $newProcess->pid,
					'command' => $newProcess->command,
					'created' => $newProcess->created->format(DateTimeFormat::MYSQL)
				])) {
					throw new ProcessPersistenceException(sprintf('The process with PID %s already exists.', $pid));
				}
			}

			$result = $this->_selectOne(['pid' => $pid]);

			$this->db->commit();

			return $result;
		} catch (\Exception $exception) {
			throw new ProcessPersistenceException(sprintf('Cannot save process with PID %s.', $pid), $exception);
		}
	}

	public function delete(Entity\Process $process)
	{
		try {
			if (!$this->db->delete(static::$table_name, [
				'pid' => $process->pid
			])) {
				throw new ProcessPersistenceException(sprintf('The process with PID %s doesn\'t exists.', $process->pi));
			}
		} catch (\Exception $exception) {
			throw new ProcessPersistenceException(sprintf('Cannot delete process with PID %s.', $process->pid), $exception);
		}
	}

	/**
	 * Clean the process table of inactive physical processes
	 */
	private function deleteInactive()
	{
		$this->db->transaction();

		try {
			$processes = $this->db->select('process', ['pid']);
			while ($process = $this->db->fetch($processes)) {
				if (!posix_kill($process['pid'], 0)) {
					$this->db->delete('process', ['pid' => $process['pid']]);
				}
			}
			$this->db->close($processes);
		} catch (\Exception $exception) {
			throw new ProcessPersistenceException('Cannot delete inactive process', $exception);
		} finally {
			$this->db->commit();
		}
	}
}
