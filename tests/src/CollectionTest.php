<?php

namespace Friendica\Test\src;

use Friendica\Test\MockedTest;
use Friendica\Test\Util\CollectionDouble;
use Friendica\Test\Util\EntityDouble;

class CollectionTest extends MockedTest
{
	/**
	 * Test if the BaseCollection::column() works as expected
	 */
	public function testGetArrayCopy()
	{
		$collection = new CollectionDouble();
		$collection->append(new EntityDouble('test', 23, new \DateTime('now', new \DateTimeZone('UTC')), 'privTest'));
		$collection->append(new EntityDouble('test2', 25, new \DateTime('now', new \DateTimeZone('UTC')), 'privTest23'));

		self::assertEquals(['test', 'test2'], $collection->column('protString'));
		self::assertEmpty($collection->column('privString'));
		self::assertEquals([23,25], $collection->column('protInt'));
	}
}
