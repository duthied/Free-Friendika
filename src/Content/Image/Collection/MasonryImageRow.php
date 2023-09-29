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

namespace Friendica\Content\Image\Collection;

use Friendica\Content\Image\Entity;
use Friendica\BaseCollection;
use Friendica\Content\Image\Entity\MasonryImage;

class MasonryImageRow extends BaseCollection
{
	/** @var ?float */
	protected $heightRatio;

	/**
	 * @param MasonryImage[] $entities
	 * @param int|null       $totalCount
	 * @param float|null     $heightRatio
	 */
	public function __construct(array $entities = [], int $totalCount = null, float $heightRatio = null)
	{
		parent::__construct($entities, $totalCount);

		$this->heightRatio = $heightRatio;
	}

	/**
	 * @return Entity\MasonryImage
	 */
	public function current(): Entity\MasonryImage
	{
		return parent::current();
	}

	public function getHeightRatio(): ?float
	{
		return $this->heightRatio;
	}
}
