<?php

namespace Friendica\Core\Worker\Factory;

use Friendica\BaseFactory;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Core\Worker\Entity;

class Process extends BaseFactory implements ICanCreateFromTableRow
{
	public function createFromTableRow(array $row): Entity\Process
	{
		return new Entity\Process(
			$row['pid'],
			$row['command'],
			new \DateTime($row['created'] ?? 'now', new \DateTimeZone('UTC'))
		);
	}

	/**
	 * Creates a new process entry for a given PID
	 *
	 * @param int $pid
	 *
	 * @return Entity\Process
	 */
	public function create(int $pid): Entity\Process
	{
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

		$command = basename($trace[0]['file']);

		return $this->createFromTableRow([
			'pid'     => $pid,
			'command' => $command,
		]);
	}
}
