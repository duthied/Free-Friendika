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
