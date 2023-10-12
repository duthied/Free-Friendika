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

namespace Friendica\Content\Post\Collection;

use Friendica\BaseCollection;
use Friendica\Content\Post\Entity;

class PostMedias extends BaseCollection
{
	/**
	 * @param Entity\PostMedia[] $entities
	 * @param int|null                   $totalCount
	 */
	public function __construct(array $entities = [], int $totalCount = null)
	{
		parent::__construct($entities, $totalCount);
	}

	/**
	 * @return Entity\PostMedia
	 */
	public function current(): Entity\PostMedia
	{
		return parent::current();
	}

	/**
	 * Determine whether all the collection's item have at least one set of dimensions provided
	 *
	 * @return bool
	 */
	public function haveDimensions(): bool
	{
		return array_reduce($this->getArrayCopy(), function (bool $carry, Entity\PostMedia $item) {
			return $carry && $item->hasDimensions();
		}, true);
	}
}
