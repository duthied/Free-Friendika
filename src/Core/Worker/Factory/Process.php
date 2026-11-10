<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Worker\Factory;

use Friendica\BaseFactory;
use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Core\Worker\Entity;

class Process extends BaseFactory implements ICanCreateFromTableRow
{
	public function determineHost(?string $hostname = null): string
	{
		return strtolower($hostname ?? php_uname('n'));
	}

	public function createFromTableRow(array $row): Entity\Process
	{
		return new Entity\Process(
			$row['pid'],
			$row['command'],
			$this->determineHost($row['hostname'] ?? null),
			new \DateTime($row['created'] ?? 'now', new \DateTimeZone('UTC')),
		);
	}
}
