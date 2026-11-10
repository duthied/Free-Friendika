<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Federation\Factory;

use Friendica\Capabilities\ICanCreateFromTableRow;
use Friendica\Database\DBA;
use Friendica\Federation\Entity;
use GuzzleHttp\Psr7\Uri;

class GServer extends \Friendica\BaseFactory implements ICanCreateFromTableRow
{
	/**
	 * @inheritDoc
	 */
	public function createFromTableRow(array $row): Entity\GServer
	{
		return new Entity\GServer(
			new Uri($row['url']),
			new Uri($row['nurl']),
			$row['version'],
			$row['site_name'],
			$row['info'] ?? '',
			$row['register_policy'],
			$row['registered-users'],
			$row['poco'] ? new Uri($row['poco']) : null,
			$row['noscrape'] ? new Uri($row['noscrape']) : null,
			$row['network'],
			$row['platform'],
			$row['relay-subscribe'],
			$row['relay-scope'],
			new \DateTimeImmutable($row['created']),
			$row['last_poco_query'] !== DBA::NULL_DATETIME ? new \DateTimeImmutable($row['last_poco_query']) : null,
			$row['last_contact'] !== DBA::NULL_DATETIME ? new \DateTimeImmutable($row['last_contact']) : null,
			$row['last_failure'] !== DBA::NULL_DATETIME ? new \DateTimeImmutable($row['last_failure']) : null,
			$row['directory-type'],
			$row['detection-method'],
			$row['failed'],
			$row['next_contact'] !== DBA::NULL_DATETIME ? new \DateTimeImmutable($row['next_contact']) : null,
			$row['protocol'],
			$row['active-week-users'],
			$row['active-month-users'],
			$row['active-halfyear-users'],
			$row['local-posts'],
			$row['local-comments'],
			$row['blocked'],
			$row['id'],
		);
	}
}
