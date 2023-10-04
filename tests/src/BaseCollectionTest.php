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

namespace Friendica\Test\src;

use Friendica\BaseCollection;
use Friendica\BaseEntity;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;

class BaseCollectionTest extends TestCase
{
	public function testChunk()
	{
		$entity1 = \Mockery::mock(BaseEntity::class);
		$entity2 = \Mockery::mock(BaseEntity::class);
		$entity3 = \Mockery::mock(BaseEntity::class);
		$entity4 = \Mockery::mock(BaseEntity::class);

		$collection = new BaseCollection([$entity1, $entity2]);

		$this->assertEquals([new BaseCollection([$entity1]), new BaseCollection([$entity2])], $collection->chunk(1));
		$this->assertEquals([new BaseCollection([$entity1, $entity2])], $collection->chunk(2));

		$collection = new BaseCollection([$entity1, $entity2, $entity3]);

		$this->assertEquals([new BaseCollection([$entity1]), new BaseCollection([$entity2]), new BaseCollection([$entity3])], $collection->chunk(1));
		$this->assertEquals([new BaseCollection([$entity1, $entity2]), new BaseCollection([$entity3])], $collection->chunk(2));
		$this->assertEquals([new BaseCollection([$entity1, $entity2, $entity3])], $collection->chunk(3));

		$collection = new BaseCollection([$entity1, $entity2, $entity3, $entity4]);

		$this->assertEquals([new BaseCollection([$entity1, $entity2]), new BaseCollection([$entity3, $entity4])], $collection->chunk(2));
		$this->assertEquals([new BaseCollection([$entity1, $entity2, $entity3]), new BaseCollection([$entity4])], $collection->chunk(3));
		$this->assertEquals([new BaseCollection([$entity1, $entity2, $entity3, $entity4])], $collection->chunk(4));
	}

	public function testChunkLengthException()
	{
		$this->expectException(\RangeException::class);

		$entity1 = \Mockery::mock(BaseEntity::class);

		$collection = new BaseCollection([$entity1]);

		$collection->chunk(0);
	}
}
