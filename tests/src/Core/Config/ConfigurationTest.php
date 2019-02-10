<?php

namespace Friendica\Test\Core\Config;

use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\Config\Cache\IConfigCache;
use Friendica\Core\Config\Configuration;
use Friendica\Test\MockedTest;

class ConfigurationTest extends MockedTest
{
	/**
	 * Test the configuration initialization
	 */
	public function testSetUp()
	{
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(false)->once();

		$configuration = new Configuration($configCache, $configAdapter);

		$this->assertInstanceOf(IConfigCache::class, $configuration->getCache());
	}

	/**
	 * Test if the configuration load() method
	 */
	public function testCacheLoad()
	{
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->twice();
		// constructor loading
		$configAdapter->shouldReceive('load')->andReturn([])->once();
		// expected loading
		$configAdapter->shouldReceive('load')->andReturn(['testing' => ['test' => 'it']])->once();

		$configuration = new Configuration($configCache, $configAdapter);
		$configuration->load('testing');

		$this->assertEquals('it', $configuration->get('testing', 'test'));
		$this->assertEquals('it', $configuration->getCache()->get('testing', 'test'));
	}

	/**
	 * Test if the configuration load() method with overwrite
	 */
	public function testCacheLoadDouble()
	{
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->times(3);
		// constructor loading
		$configAdapter->shouldReceive('load')->andReturn([])->once();
		// expected loading
		$configAdapter->shouldReceive('load')->andReturn(['testing' => ['test' => 'it']])->once();
		// expected next loading
		$configAdapter->shouldReceive('load')->andReturn(['testing' => ['test' => 'again']])->once();

		$configuration = new Configuration($configCache, $configAdapter);
		$configuration->load('testing');

		$this->assertEquals('it', $configuration->get('testing', 'test'));
		$this->assertEquals('it', $configuration->getCache()->get('testing', 'test'));

		$configuration->load('testing');

		$this->assertEquals('again', $configuration->get('testing', 'test'));
		$this->assertEquals('again', $configuration->getCache()->get('testing', 'test'));
	}

	/**
	 * Test if the configuration get() and set() methods without adapter
	 */
	public function testSetGetWithoutDB()
	{
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(false)->twice();

		$configuration = new Configuration($configCache, $configAdapter);

		$this->assertTrue($configuration->set('test', 'it', 'now'));

		$this->assertEquals('now', $configuration->get('test', 'it'));
		$this->assertEquals('now', $configuration->getCache()->get('test', 'it'));
	}

	/**
	 * Test if the configuration get() and set() methods with adapter
	 */
	public function testSetGetWithDB()
	{
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->twice();
		// constructor loading
		$configAdapter->shouldReceive('load')->andReturn([])->once();
		$configAdapter->shouldReceive('set')->with('test', 'it', 'now')->andReturn(true)->once();

		$configuration = new Configuration($configCache, $configAdapter);

		$this->assertTrue($configuration->set('test', 'it', 'now'));

		$this->assertEquals('now', $configuration->get('test', 'it'));
		$this->assertEquals('now', $configuration->getCache()->get('test', 'it'));
	}

	/**
	 * Test the configuration get() method with wrong value and no db
	 */
	public function testGetWrongWithoutDB()
	{
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(false)->times(4);

		$configuration = new Configuration($configCache, $configAdapter);

		// without refresh
		$this->assertNull($configuration->get('test', 'it'));

		/// beware that the cache returns '!<unset>!' and not null for a non existing value
		$this->assertEquals('!<unset>!', $configuration->getCache()->get('test', 'it'));

		// with default value
		$this->assertEquals('default', $configuration->get('test', 'it', 'default'));

		// with default value and refresh
		$this->assertEquals('default', $configuration->get('test', 'it', 'default', true));
	}

	/**
	 * Test the configuration get() method with refresh
	 */
	public function testGetWithRefresh()
	{
		$configCache = new ConfigCache(['test' => ['it' => 'now']]);
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->times(3);
		// constructor loading
		$configAdapter->shouldReceive('load')->andReturn([])->once();
		$configAdapter->shouldReceive('get')->with('test', 'it')->andReturn('again')->once();
		$configAdapter->shouldReceive('get')->with('test', 'not')->andReturn('!<unset>!')->once();

		$configuration = new Configuration($configCache, $configAdapter);

		// without refresh
		$this->assertEquals('now', $configuration->get('test', 'it'));
		$this->assertEquals('now', $configuration->getCache()->get('test', 'it'));

		// with refresh
		$this->assertEquals('again', $configuration->get('test', 'it', null, true));
		$this->assertEquals('again', $configuration->getCache()->get('test', 'it'));

		// without refresh and wrong value and default
		$this->assertEquals('default', $configuration->get('test', 'not', 'default'));
		$this->assertEquals('!<unset>!', $configuration->getCache()->get('test', 'not'));
	}

	/**
	 * Test the configuration delete() method without adapter
	 */
	public function testDeleteWithoutDB()
	{
		$configCache = new ConfigCache(['test' => ['it' => 'now']]);
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(false)->times(3);

		$configuration = new Configuration($configCache, $configAdapter);

		$this->assertEquals('now', $configuration->get('test', 'it'));
		$this->assertEquals('now', $configuration->getCache()->get('test', 'it'));

		$this->assertTrue($configuration->delete('test', 'it'));
		$this->assertNull($configuration->get('test', 'it'));
		$this->assertEquals('!<unset>!', $configuration->getCache()->get('test', 'it'));

		$this->assertEmpty($configuration->getCache()->getAll());
	}

	/**
	 * Test the configuration delete() method with adapter
	 */
	public function testDeleteWithDB()
	{
		$configCache = new ConfigCache(['test' => ['it' => 'now', 'quarter' => 'true']]);
		$configAdapter = \Mockery::mock('Friendica\Core\Config\Adapter\IConfigAdapter');
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->times(5);
		// constructor loading
		$configAdapter->shouldReceive('load')->andReturn([])->once();
		$configAdapter->shouldReceive('delete')->with('test', 'it')->andReturn(false)->once();

		$configAdapter->shouldReceive('delete')->with('test', 'second')->andReturn(true)->once();
		$configAdapter->shouldReceive('delete')->with('test', 'third')->andReturn(false)->once();
		$configAdapter->shouldReceive('delete')->with('test', 'quarter')->andReturn(true)->once();

		$configuration = new Configuration($configCache, $configAdapter);

		$this->assertEquals('now', $configuration->get('test', 'it'));
		$this->assertEquals('now', $configuration->getCache()->get('test', 'it'));

		// delete from cache only
		$this->assertTrue($configuration->delete('test', 'it'));
		// delete from db only
		$this->assertTrue($configuration->delete('test', 'second'));
		// no delete
		$this->assertFalse($configuration->delete('test', 'third'));
		// delete both
		$this->assertTrue($configuration->delete('test', 'quarter'));

		$this->assertEmpty($configuration->getCache()->getAll());
	}
}
