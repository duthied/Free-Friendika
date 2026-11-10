<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Worker\Entity;

use DateTime;
use Friendica\BaseEntity;

/**
 * @property-read int $pid
 * @property-read string $command
 * @property-read string $hostname
 * @property-read DateTime $created
 */
class Process extends BaseEntity
{
	/** @var int */
	protected $pid;
	/** @var string */
	protected $command;
	/** @var string */
	protected $hostname;
	/** @var DateTime */
	protected $created;

	/**
	 * @param int      $pid
	 * @param string   $command
	 * @param string   $hostname
	 * @param DateTime $created
	 */
	public function __construct(int $pid, string $command, string $hostname, DateTime $created)
	{
		$this->pid      = $pid;
		$this->command  = $command;
		$this->hostname = $hostname;
		$this->created  = $created;
	}
}
