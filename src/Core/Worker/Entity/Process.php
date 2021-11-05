<?php

namespace Friendica\Core\Worker\Entity;

use DateTime;
use Friendica\BaseEntity;

/**
 * @property-read int $pid
 * @property-read string $command
 * @property-read DateTime $created
 */
class Process extends BaseEntity
{
	/** @var int */
	protected $pid;
	/** @var string */
	protected $command;
	/** @var DateTime */
	protected $created;

	/**
	 * @param int       $pid
	 * @param string    $command
	 * @param DateTime $created
	 */
	public function __construct(int $pid, string $command, DateTime $created)
	{
		$this->pid     = $pid;
		$this->command = $command;
		$this->created = $created;
	}
}
