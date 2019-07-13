<?php

namespace Friendica\Test\src\Core\Config;

use Friendica\Core\Config\Adapter\IConfigAdapter;
use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\Config\Configuration;
use Friendica\Test\MockedTest;

class ConfigurationTest extends MockedTest
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
	 * Test the configuration initialization
	 */
	public function testSetUp()
	{
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock(IConfigAdapter::class);
		$configAdapter->shouldReceive('isConnected')->andReturn(false)->once();

		$configuration = new Configuration($configCache, $configAdapter);

		$this->assertInstanceOf(ConfigCache::class, $configuration->getCache());
	}

	/**
	 * Test the configuration load() method
	 */
	public function testCacheLoad()
	{
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock(IConfigAdapter::class);
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->times(3);
		// constructor loading
		$configAdapter->shouldReceive('load')->andReturn([])->once();
		// expected loading
		$configAdapter->shouldReceive('load')->andReturn(['testing' => ['test' => 'it']])->once();
		$configAdapter->shouldReceive('isLoaded')->with('testing', 'test')->andReturn(true)->once();

		$configuration = new Configuration($configCache, $configAdapter);
		$configuration->load('testing');

		$this->assertEquals('it', $configuration->get('testing', 'test'));
		$this->assertEquals('it', $configuration->getCache()->get('testing', 'test'));
	}

	/**
	 * Test the configuration load() method with overwrite
	 */
	public function testCacheLoadDouble()
	{
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock(IConfigAdapter::class);
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->times(5);
		// constructor loading
		$configAdapter->shouldReceive('load')->andReturn([])->once();
		// expected loading
		$configAdapter->shouldReceive('load')->andReturn(['testing' => ['test' => 'it']])->once();
		$configAdapter->shouldReceive('isLoaded')->with('testing', 'test')->andReturn(true)->twice();
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
	 * Test the configuration get() and set() methods without adapter
	 * @dataProvider dataTests
	 */
	public function testSetGetWithoutDB($data)
	{
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock(IConfigAdapter::class);
		$configAdapter->shouldReceive('isConnected')->andReturn(false)->times(3);

		$configuration = new Configuration($configCache, $configAdapter);

		$this->assertTrue($configuration->set('test', 'it', $data));

		$this->assertEquals($data, $configuration->get('test', 'it'));
		$this->assertEquals($data, $configuration->getCache()->get('test', 'it'));
	}

	/**
	 * Test the configuration get() and set() methods with adapter
	 * @dataProvider dataTests
	 */
	public function testSetGetWithDB($data)
	{
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock(IConfigAdapter::class);
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->times(3);
		// constructor loading
		$configAdapter->shouldReceive('load')->andReturn([])->once();
		$configAdapter->shouldReceive('isLoaded')->with('test', 'it')->andReturn(true)->once();
		$configAdapter->shouldReceive('set')->with('test', 'it', $data)->andReturn(true)->once();

		$configuration = new Configuration($configCache, $configAdapter);

		$this->assertTrue($configuration->set('test', 'it', $data));

		$this->assertEquals($data, $configuration->get('test', 'it'));
		$this->assertEquals($data, $configuration->getCache()->get('test', 'it'));
	}

	/**
	 * Test the configuration get() method with wrong value and no db
	 */
	public function testGetWrongWithoutDB()
	{
		$configCache = new ConfigCache();
		$configAdapter = \Mockery::mock(IConfigAdapter::class);
		$configAdapter->shouldReceive('isConnected')->andReturn(false)->times(4);

		$configuration = new Configuration($configCache, $configAdapter);

		// without refresh
		$this->assertNull($configuration->get('test', 'it'));

		/// beware that the cache returns '!<unset>!' and not null for a non existing value
		$this->assertNull($configuration->getCache()->get('test', 'it'));

		// with default value
		$this->assertEquals('default', $configuration->get('test', 'it', 'default'));

		// with default value and refresh
		$this->assertEquals('default', $configuration->get('test', 'it', 'default', true));
	}

	/**
	 * Test the configuration get() method with refresh
	 * @dataProvider dataTests
	 */
	public function testGetWithRefresh($data)
	{
		$configCache = new ConfigCache(['test' => ['it' => 'now']]);
		$configAdapter = \Mockery::mock(IConfigAdapter::class);
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->times(4);
		// constructor loading
		$configAdapter->shouldReceive('load')->andReturn([])->once();
		$configAdapter->shouldReceive('isLoaded')->with('test', 'it')->andReturn(true)->twice();
		$configAdapter->shouldReceive('get')->with('test', 'it')->andReturn($data)->once();
		$configAdapter->shouldReceive('isLoaded')->with('test', 'not')->andReturn(false)->once();
		$configAdapter->shouldReceive('get')->with('test', 'not')->andReturn(null)->once();

		$configuration = new Configuration($configCache, $configAdapter);

		// without refresh
		$this->assertEquals('now', $configuration->get('test', 'it'));
		$this->assertEquals('now', $configuration->getCache()->get('test', 'it'));

		// with refresh
		$this->assertEquals($data, $configuration->get('test', 'it', null, true));
		$this->assertEquals($data, $configuration->getCache()->get('test', 'it'));

		// without refresh and wrong value and default
		$this->assertEquals('default', $configuration->get('test', 'not', 'default'));
		$this->assertNull($configuration->getCache()->get('test', 'not'));
	}

	/**
	 * Test the configuration get() method with different isLoaded settings
	 * @dataProvider dataTests
	 */
	public function testGetWithoutLoaded($data)
	{
		$configCache = new ConfigCache(['test' => ['it' => 'now']]);
		$configAdapter = \Mockery::mock(IConfigAdapter::class);
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->times(4);
		// constructor loading
		$configAdapter->shouldReceive('load')->andReturn([])->once();

		$configAdapter->shouldReceive('isLoaded')->with('test', 'it')->andReturn(false)->once();
		$configAdapter->shouldReceive('get')->with('test', 'it')->andReturn(null)->once();

		$configAdapter->shouldReceive('isLoaded')->with('test', 'it')->andReturn(false)->once();
		$configAdapter->shouldReceive('get')->with('test', 'it')->andReturn($data)->once();

		$configAdapter->shouldReceive('isLoaded')->with('test', 'it')->andReturn(true)->once();

		$configuration = new Configuration($configCache, $configAdapter);

		// first run is not loaded and no data is found in the DB
		$this->assertEquals('now', $configuration->get('test', 'it'));
		$this->assertEquals('now', $configuration->getCache()->get('test', 'it'));

		// second run is not loaded, but now data is found in the db (overwrote cache)
		$this->assertEquals($data, $configuration->get('test', 'it'));
		$this->assertEquals($data, $configuration->getCache()->get('test', 'it'));

		// third run is loaded and therefore cache is used
		$this->assertEquals($data, $configuration->get('test', 'it'));
		$this->assertEquals($data, $configuration->getCache()->get('test', 'it'));
	}

	/**
	 * Test the configuration delete() method without adapter
	 * @dataProvider dataTests
	 */
	public function testDeleteWithoutDB($data)
	{
		$configCache = new ConfigCache(['test' => ['it' => $data]]);
		$configAdapter = \Mockery::mock(IConfigAdapter::class);
		$configAdapter->shouldReceive('isConnected')->andReturn(false)->times(4);

		$configuration = new Configuration($configCache, $configAdapter);

		$this->assertEquals($data, $configuration->get('test', 'it'));
		$this->assertEquals($data, $configuration->getCache()->get('test', 'it'));

		$this->assertTrue($configuration->delete('test', 'it'));
		$this->assertNull($configuration->get('test', 'it'));
		$this->assertNull($configuration->getCache()->get('test', 'it'));

		$this->assertEmpty($configuration->getCache()->getAll());
	}

	/**
	 * Test the configuration delete() method with adapter
	 */
	public function testDeleteWithDB()
	{
		$configCache = new ConfigCache(['test' => ['it' => 'now', 'quarter' => 'true']]);
		$configAdapter = \Mockery::mock(IConfigAdapter::class);
		$configAdapter->shouldReceive('isConnected')->andReturn(true)->times(6);
		// constructor loading
		$configAdapter->shouldReceive('load')->andReturn([])->once();
		$configAdapter->shouldReceive('isLoaded')->with('test', 'it')->andReturn(true)->once();

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
