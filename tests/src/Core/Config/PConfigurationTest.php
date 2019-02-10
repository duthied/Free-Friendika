<?php

namespace Friendica\Test\Core\Config;

use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\Config\PConfiguration;
use Friendica\Test\MockedTest;

class PConfigurationTest extends MockedTest
{
	/**
	 * Test if the configuration load() method
	 */
	public function testCacheLoad()
	{
		$uid = 234;
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IPConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->once();
		// expected loading
		$configAdapter->shouldReceive('load')->andReturn(['testing' => ['test' => 'it']])->once();

		$configuration = new PConfiguration($configCache, $configAdapter);
		$configuration->load($uid, 'testing');

		$this->assertEquals('it', $configuration->get($uid, 'testing', 'test'));
	}

	/**
	 * Test if the configuration load() method with overwrite
	 */
	public function testCacheLoadDouble()
	{
		$uid = 234;
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IPConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->twice();
		// expected loading
		$configAdapter->shouldReceive('load')->andReturn(['testing' => ['test' => 'it']])->once();
		// expected next loading
		$configAdapter->shouldReceive('load')->andReturn(['testing' => ['test' => 'again']])->once();

		$configuration = new PConfiguration($configCache, $configAdapter);
		$configuration->load($uid, 'testing');

		$this->assertEquals('it', $configuration->get($uid, 'testing', 'test'));

		$configuration->load($uid, 'testing');

		$this->assertEquals('again', $configuration->get($uid, 'testing', 'test'));
	}

	/**
	 * Test if the configuration get() and set() methods without adapter
	 */
	public function testSetGetWithoutDB()
	{
		$uid = 234;
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IPConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(false)->once();

		$configuration = new PConfiguration($configCache, $configAdapter);

		$this->assertTrue($configuration->set($uid, 'test', 'it', 'now'));

		$this->assertEquals('now', $configuration->get($uid, 'test', 'it'));
	}

	/**
	 * Test if the configuration get() and set() methods with adapter
	 */
	public function testSetGetWithDB()
	{
		$uid = 234;
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IPConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->once();
		$configAdapter->shouldReceive('set')->with($uid, 'test', 'it', 'now')->andReturn(true)->once();

		$configuration = new PConfiguration($configCache, $configAdapter);

		$this->assertTrue($configuration->set($uid, 'test', 'it', 'now'));

		$this->assertEquals('now', $configuration->get($uid, 'test', 'it'));
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
	 */
	public function testGetWithRefresh()
	{
		$uid = 234;
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IPConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->times(3);
		$configAdapter->shouldReceive('get')->with($uid, 'test', 'it')->andReturn('now')->once();
		$configAdapter->shouldReceive('get')->with($uid, 'test', 'it')->andReturn('again')->once();
		$configAdapter->shouldReceive('get')->with($uid, 'test', 'not')->andReturn('!<unset>!')->once();

		$configuration = new PConfiguration($configCache, $configAdapter);

		// without refresh
		$this->assertEquals('now', $configuration->get($uid, 'test', 'it'));
		// use the cache again
		$this->assertEquals('now', $configuration->get($uid, 'test', 'it'));

		// with refresh (and load the second value out of the db)
		$this->assertEquals('again', $configuration->get($uid, 'test', 'it', null, true));

		// without refresh and wrong value and default
		$this->assertEquals('default', $configuration->get($uid, 'test', 'not', 'default'));
	}

	/**
	 * Test the configuration delete() method without adapter
	 */
	public function testDeleteWithoutDB()
	{
		$uid = 234;
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IPConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(false)->times(3);

		$configuration = new PConfiguration($configCache, $configAdapter);

		$this->assertTrue($configuration->set($uid, 'test', 'it', 'now'));
		$this->assertEquals('now', $configuration->get($uid, 'test', 'it'));

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
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->times(5);
		$configAdapter->shouldReceive('set')->with($uid, 'test', 'it', 'now')->andReturn(false)->once();
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
