<?php

namespace Friendica\Test\Core\Config;

use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\Config\PConfiguration;
use Friendica\Test\MockedTest;

class PConfigurationTest extends MockedTest
{
	public function dataTests()
	{
		return [
			'string'       => ['data' => 'it'],
			'boolTrue'     => ['data' => true],
			'boolFalse'    => ['data' => false],
			'integer'      => ['data' => 235],
			'decimal'      => ['data' => 2.456],
			'array'        => ['data' => ['1', 2, '3', true, false]],
			'boolIntTrue'  => ['data' => 1],
			'boolIntFalse' => ['Data' => 0],
		];
	}

	/**
	 * Test the configuration load() method
	 */
	public function testCacheLoad()
	{
		$uid = 234;
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IPConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->twice();
		// expected loading
		$configAdapter->shouldReceive('load')
			->with($uid, 'testing')
			->andReturn(['testing' => ['test' => 'it']])
			->once();
		$configAdapter->shouldReceive('isLoaded')->with($uid, 'testing', 'test')->andReturn(true)->once();

		$configuration = new PConfiguration($configCache, $configAdapter);
		$configuration->load($uid, 'testing');

		$this->assertEquals('it', $configuration->get($uid, 'testing', 'test'));
	}

	/**
	 * Test the configuration load() method with overwrite
	 */
	public function testCacheLoadDouble()
	{
		$uid = 234;
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IPConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->times(4);
		// expected loading
		$configAdapter->shouldReceive('load')->with($uid, 'testing')->andReturn(['testing' => ['test' => 'it']])->once();
		$configAdapter->shouldReceive('isLoaded')->with($uid, 'testing', 'test')->andReturn(true)->twice();
		// expected next loading
		$configAdapter->shouldReceive('load')->andReturn(['testing' => ['test' => 'again']])->once();

		$configuration = new PConfiguration($configCache, $configAdapter);
		$configuration->load($uid, 'testing');

		$this->assertEquals('it', $configuration->get($uid, 'testing', 'test'));

		$configuration->load($uid, 'testing');

		$this->assertEquals('again', $configuration->get($uid, 'testing', 'test'));
	}

	/**
	 * Test the configuration get() and set() methods without adapter
	 * @dataProvider dataTests
	 */
	public function testSetGetWithoutDB($data)
	{
		$uid = 234;
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IPConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(false)->times(2);

		$configuration = new PConfiguration($configCache, $configAdapter);

		$this->assertTrue($configuration->set($uid, 'test', 'it', $data));

		$this->assertEquals($data, $configuration->get($uid, 'test', 'it'));
	}

	/**
	 * Test the configuration get() and set() methods with adapter
	 * @dataProvider dataTests
	 */
	public function testSetGetWithDB($data)
	{
		$uid = 234;
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IPConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->times(2);
		$configAdapter->shouldReceive('isLoaded')->with($uid, 'test', 'it')->andReturn(true)->once();
		$configAdapter->shouldReceive('set')->with($uid, 'test', 'it', $data)->andReturn(true)->once();

		$configuration = new PConfiguration($configCache, $configAdapter);

		$this->assertTrue($configuration->set($uid, 'test', 'it', $data));

		$this->assertEquals($data, $configuration->get($uid, 'test', 'it'));
	}

	/**
	 * Test the configuration get() method with wrong value and no db
	 */
	public function testGetWrongWithoutDB()
	{
		$uid = 234;
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IPConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(false)->times(3);

		$configuration = new PConfiguration($configCache, $configAdapter);

		// without refresh
		$this->assertNull($configuration->get($uid, 'test', 'it'));

		// with default value
		$this->assertEquals('default', $configuration->get($uid, 'test', 'it', 'default'));

		// with default value and refresh
		$this->assertEquals('default', $configuration->get($uid, 'test', 'it', 'default', true));
	}

	/**
	 * Test the configuration get() method with refresh
	 * @dataProvider dataTests
	 */
	public function testGetWithRefresh($data)
	{
		$uid = 234;
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IPConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->times(4);
		$configAdapter->shouldReceive('isLoaded')->with($uid, 'test', 'it')->andReturn(false)->once();
		$configAdapter->shouldReceive('get')->with($uid, 'test', 'it')->andReturn('now')->once();
		$configAdapter->shouldReceive('isLoaded')->with($uid, 'test', 'it')->andReturn(true)->twice();
		$configAdapter->shouldReceive('get')->with($uid, 'test', 'it')->andReturn($data)->once();
		$configAdapter->shouldReceive('isLoaded')->with($uid, 'test', 'not')->andReturn(false)->once();
		$configAdapter->shouldReceive('get')->with($uid, 'test', 'not')->andReturn('!<unset>!')->once();

		$configuration = new PConfiguration($configCache, $configAdapter);

		// without refresh
		$this->assertEquals('now', $configuration->get($uid, 'test', 'it'));
		// use the cache again
		$this->assertEquals('now', $configuration->get($uid, 'test', 'it'));

		// with refresh (and load the second value out of the db)
		$this->assertEquals($data, $configuration->get($uid, 'test', 'it', null, true));

		// without refresh and wrong value and default
		$this->assertEquals('default', $configuration->get($uid, 'test', 'not', 'default'));
	}

	/**
	 * Test the configuration get() method with different isLoaded settings
	 * @dataProvider dataTests
	 */
	public function testGetWithoutLoaded($data)
	{
		$uid = 234;
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IPConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->times(3);

		$configAdapter->shouldReceive('isLoaded')->with($uid, 'test', 'it')->andReturn(false)->once();
		$configAdapter->shouldReceive('get')->with($uid, 'test', 'it')->andReturn('!<unset>!')->once();

		$configAdapter->shouldReceive('isLoaded')->with($uid, 'test', 'it')->andReturn(false)->once();
		$configAdapter->shouldReceive('get')->with($uid, 'test', 'it')->andReturn($data)->once();

		$configAdapter->shouldReceive('isLoaded')->with($uid, 'test', 'it')->andReturn(true)->once();

		$configuration = new PConfiguration($configCache, $configAdapter);

		// first run is not loaded and no data is found in the DB
		$this->assertNull($configuration->get($uid, 'test', 'it'));

		// second run is not loaded, but now data is found in the db (overwrote cache)
		$this->assertEquals($data, $configuration->get($uid,'test', 'it'));

		// third run is loaded and therefore cache is used
		$this->assertEquals($data, $configuration->get($uid,'test', 'it'));
	}

	/**
	 * Test the configuration delete() method without adapter
	 * @dataProvider dataTests
	 */
	public function testDeleteWithoutDB($data)
	{
		$uid = 234;
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IPConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(false)->times(4);

		$configuration = new PConfiguration($configCache, $configAdapter);

		$this->assertTrue($configuration->set($uid, 'test', 'it', $data));
		$this->assertEquals($data, $configuration->get($uid, 'test', 'it'));

		$this->assertTrue($configuration->delete($uid, 'test', 'it'));
		$this->assertNull($configuration->get($uid, 'test', 'it'));
	}

	/**
	 * Test the configuration delete() method with adapter
	 */
	public function testDeleteWithDB()
	{
		$uid = 234;
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IPConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->times(6);
		$configAdapter->shouldReceive('set')->with($uid, 'test', 'it', 'now')->andReturn(false)->once();
		$configAdapter->shouldReceive('isLoaded')->with($uid, 'test', 'it')->andReturn(true)->once();

		$configAdapter->shouldReceive('delete')->with($uid, 'test', 'it')->andReturn(false)->once();

		$configAdapter->shouldReceive('delete')->with($uid, 'test', 'second')->andReturn(true)->once();
		$configAdapter->shouldReceive('delete')->with($uid, 'test', 'third')->andReturn(false)->once();
		$configAdapter->shouldReceive('delete')->with($uid, 'test', 'quarter')->andReturn(true)->once();

		$configuration = new PConfiguration($configCache, $configAdapter);

		$this->assertFalse($configuration->set($uid, 'test', 'it', 'now'));
		$this->assertEquals('now', $configuration->get($uid, 'test', 'it'));

		// delete from set
		$this->assertTrue($configuration->delete($uid, 'test', 'it'));
		// delete from db only
		$this->assertTrue($configuration->delete($uid, 'test', 'second'));
		// no delete
		$this->assertFalse($configuration->delete($uid, 'test', 'third'));
		// delete both
		$this->assertTrue($configuration->delete($uid, 'test', 'quarter'));
	}
}
