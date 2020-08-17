<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\IMemoryCache;

abstract class MemoryCacheTest extends CacheTest
{
	/**
	 * @var \Friendica\Core\Cache\IMemoryCache
	 */
	protected $instance;

	protected function setUp()
	{
		parent::setUp();

		if (!($this->instance instanceof IMemoryCache)) {
			throw new \Exception('MemoryCacheTest unsupported');
		}
	}

	/**
	 * @small
	 * @dataProvider dataSimple
	 */
	function testCompareSet($value1, $value2)
	{
		$this->assertNull($this->instance->get('value1'));

		$this->instance->add('value1', $value1);
		$received = $this->instance->get('value1');
		$this->assertEquals($value1, $received, 'Value received from cache not equal to the original');

		$this->instance->compareSet('value1', $value1, $value2);
		$received = $this->instance->get('value1');
		$this->assertEquals($value2, $received, 'Value not overwritten by compareSet');
	}

	/**
	 * @small
	 * @dataProvider dataSimple
	 */
	function testNegativeCompareSet($value1, $value2)
	{
		$this->assertNull($this->instance->get('value1'));

		$this->instance->add('value1', $value1);
		$received = $this->instance->get('value1');
		$this->assertEquals($value1, $received, 'Value received from cache not equal to the original');

		$this->instance->compareSet('value1', 'wrong', $value2);
		$received = $this->instance->get('value1');
		$this->assertNotEquals($value2, $received, 'Value was wrongly overwritten by compareSet');
		$this->assertEquals($value1, $received, 'Value was wrongly overwritten by any other value');
	}

	/**
	 * @small
	 * @dataProvider dataSimple
	 */
	function testCompareDelete($data)
	{
		$this->assertNull($this->instance->get('value1'));

		$this->instance->add('value1', $data);
		$received = $this->instance->get('value1');
		$this->assertEquals($data, $received, 'Value received from cache not equal to the original');
		$this->instance->compareDelete('value1', $data);
		$this->assertNull($this->instance->get('value1'), 'Value was not deleted by compareDelete');
	}

	/**
	 * @small
	 * @dataProvider dataSimple
	 */
	function testNegativeCompareDelete($data)
	{
		$this->assertNull($this->instance->get('value1'));

		$this->instance->add('value1', $data);
		$received = $this->instance->get('value1');
		$this->assertEquals($data, $received, 'Value received from cache not equal to the original');
		$this->instance->compareDelete('value1', 'wrong');
		$this->assertNotNull($this->instance->get('value1'), 'Value was wrongly compareDeleted');

		$this->instance->compareDelete('value1', $data);
		$this->assertNull($this->instance->get('value1'), 'Value was wrongly NOT deleted by compareDelete');
	}

	/**
	 * @small
	 * @dataProvider dataSimple
	 */
	function testAdd($value1, $value2)
	{
		$this->assertNull($this->instance->get('value1'));

		$this->instance->add('value1', $value1);

		$this->instance->add('value1', $value2);
		$received = $this->instance->get('value1');
		$this->assertNotEquals($value2, $received, 'Value was wrongly overwritten by add');
		$this->assertEquals($value1, $received, 'Value was wrongly overwritten by any other value');

		$this->instance->delete('value1');
		$this->instance->add('value1', $value2);
		$received = $this->instance->get('value1');
		$this->assertEquals($value2, $received, 'Value was not overwritten by add');
		$this->assertNotEquals($value1, $received, 'Value was not overwritten by any other value');
	}
}
