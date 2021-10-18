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

namespace Friendica\Contact\Introduction\Factory;

use Friendica\BaseFactory;
use Friendica\Contact\Introduction\Entity;
use Friendica\Capabilities\ICanCreateFromTableRow;

class Introduction extends BaseFactory implements ICanCreateFromTableRow
{
	/**
	 * @inheritDoc
	 */
	public function createFromTableRow(array $row): Entity\Introduction
	{
		return new Entity\Introduction(
			$row['uid'],
			$row['fid'],
			$row['contact-id'],
			$row['suggested-cid'],
			!empty($row['knowyou']),
			!empty($row['dupley']),
			$row['note'],
			$row['hash'],
			new \DateTime($row['datetime'], new \DateTimeZone('UTC')),
			!empty($row['blocked']),
			!empty($row['ignore']),
			$row['id'] ?? null
		);
	}
}
