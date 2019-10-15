<?php

namespace Friendica\Test\src\Core\Config;

use Friendica\Core\Config\Cache\PConfigCache;
use Friendica\Core\Config\PConfiguration;
use Friendica\Model\Config\PConfig as PConfigModel;
use Friendica\Test\MockedTest;
use Mockery;
use Mockery\MockInterface;

abstract class PConfigurationTest extends MockedTest
{
	/** @var PConfigModel|MockInterface */
	protected $configModel;

	/** @var PConfigCache */
	protected $configCache;

	/** @var PConfiguration */
	protected $testedConfig;

	/**
	 * Assert a config tree
	 *
	 * @param int    $uid  The uid to assert
	 * @param string $cat  The category to assert
	 * @param array  $data The result data array
	 */
	protected function assertConfig(int $uid, string $cat, array $data)
	{
		$result = $this->testedConfig->getCache()->getAll();

		$this->assertNotEmpty($result);
		$this->assertArrayHasKey($uid, $result);
		$this->assertArrayHasKey($cat, $result[$uid]);
		$this->assertArraySubset($data, $result[$uid][$cat]);
	}


	protected function setUp()
	{
		parent::setUp();

		// Create the config model
		$this->configModel = Mockery::mock(PConfigModel::class);
		$this->configCache = new PConfigCache();
	}

	/**
	 * @return PConfiguration
	 */
	public abstract function getInstance();

	public function dataTests()
	{
		return [
			'string'       => ['uid' => 1, 'data' => 'it'],
			'boolTrue'     => ['uid' => 2, 'data' => true],
			'boolFalse'    => ['uid' => 3, 'data' => false],
			'integer'      => ['uid' => 4, 'data' => 235],
			'decimal'      => ['uid' => 5, 'data' => 2.456],
			'array'        => ['uid' => 6, 'data' => ['1', 2, '3', true, false]],
			'boolIntTrue'  => ['uid' => 7, 'data' => 1],
			'boolIntFalse' => ['uid' => 8, 'data' => 0],
		];
	}

	public function dataConfigLoad()
	{
		$data = [
			'system' => [
				'key1' => 'value1',
				'key2' => 'value2',
				'key3' => 'value3',
			],
			'config' => [
				'key1' => 'value1a',
				'key4' => 'value4',
			],
			'other'  => [
				'key5' => 'value5',
				'key6' => 'value6',
			],
		];

		return [
			'system' => [
				'uid' => 1,
				'data'         => $data,
				'possibleCats' => [
					'system',
					'config',
					'other'
				],
				'load'         => [
					'system',
				],
			],
			'other'  => [
				'uid' => 2,
				'data'         => $data,
				'possibleCats' => [
					'system',
					'config',
					'other'
				],
				'load'         => [
					'other',
				],
			],
			'config' => [
				'uid' => 3,
				'data'         => $data,
				'possibleCats' => [
					'system',
					'config',
					'other'
				],
				'load'         => [
					'config',
				],
			],
			'all'    => [
				'uid' => 4,
				'data'         => $data,
				'possibleCats' => [
					'system',
					'config',
					'other'
				],
				'load'         => [
					'system',
					'config',
					'other'
				],
			],
		];
	}

	/**
	 * Test the configuration initialization
	 * @dataProvider dataConfigLoad
	 */
	public function testSetUp(int $uid, array $data)
	{
		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(PConfigCache::class, $this->testedConfig->getCache());

		$this->assertEmpty($this->testedConfig->getCache()->getAll());
	}

	/**
	 * Test the configuration load() method
	 */
	public function testLoad(int $uid, array $data, array $possibleCats, array $load)
	{
		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(PConfigCache::class, $this->testedConfig->getCache());

		foreach ($load as $loadedCats) {
			$this->testedConfig->load($uid, $loadedCats);
		}

		// Assert at least loaded cats are loaded
		foreach ($load as $loadedCats) {
			$this->assertConfig($uid, $loadedCats, $data[$loadedCats]);
		}
	}

	public function dataDoubleLoad()
	{
		return [
			'config' => [
				'uid' => 1,
				'data1'  => [
					'config' => [
						'key1' => 'value1',
						'key2' => 'value2',
					],
				],
				'data2'  => [
					'config' => [
						'key1' => 'overwritten!',
						'key3' => 'value3',
					],
				],
				'expect' => [
					'config' => [
						// load should overwrite values everytime!
						'key1' => 'overwritten!',
						'key2' => 'value2',
						'key3' => 'value3',
					],
				],
			],
			'other'  => [
				'uid' => 1,
				'data1'  => [
					'config' => [
						'key12' => 'data4',
						'key45' => 7,
					],
					'other'  => [
						'key1' => 'value1',
						'key2' => 'value2',
					],
				],
				'data2'  => [
					'other'  => [
						'key1' => 'overwritten!',
						'key3' => 'value3',
					],
					'config' => [
						'key45' => 45,
						'key52' => true,
					]
				],
				'expect' => [
					'other'  => [
						// load should overwrite values everytime!
						'key1' => 'overwritten!',
						'key2' => 'value2',
						'key3' => 'value3',
					],
					'config' => [
						'key12' => 'data4',
						'key45' => 45,
						'key52' => true,
					],
				],
			],
		];
	}

	/**
	 * Test the configuration load() method with overwrite
	 */
	public function testCacheLoadDouble(int $uid, array $data1, array $data2, array $expect)
	{
		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(PConfigCache::class, $this->testedConfig->getCache());

		foreach ($data1 as $cat => $data) {
			$this->testedConfig->load($uid, $cat);
		}

		// Assert at least loaded cats are loaded
		foreach ($data1 as $cat => $data) {
			$this->assertConfig($uid, $cat, $data);
		}

		foreach ($data2 as $cat => $data) {
			$this->testedConfig->load($uid, $cat);
		}
	}

	/**
	 * Test the configuration get() and set() methods without adapter
	 *
	 * @dataProvider dataTests
	 */
	public function testSetGetWithoutDB(int $uid, $data)
	{
		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(PConfigCache::class, $this->testedConfig->getCache());

		$this->assertTrue($this->testedConfig->set($uid, 'test', 'it', $data));

		$this->assertEquals($data, $this->testedConfig->get($uid, 'test', 'it'));
		$this->assertEquals($data, $this->testedConfig->getCache()->get($uid, 'test', 'it'));
	}

	/**
	 * Test the configuration get() and set() methods with a model/db
	 *
	 * @dataProvider dataTests
	 */
	public function testSetGetWithDB(int $uid, $data)
	{
		$this->configModel->shouldReceive('set')
		                  ->with($uid, 'test', 'it', $data)
		                  ->andReturn(true)
		                  ->once();

		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(PConfigCache::class, $this->testedConfig->getCache());

		$this->assertTrue($this->testedConfig->set($uid, 'test', 'it', $data));

		$this->assertEquals($data, $this->testedConfig->get($uid, 'test', 'it'));
		$this->assertEquals($data, $this->testedConfig->getCache()->get($uid, 'test', 'it'));
	}

	/**
	 * Test the configuration get() method with wrong value and no db
	 */
	public function testGetWrongWithoutDB()
	{
		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(PConfigCache::class, $this->testedConfig->getCache());

		// without refresh
		$this->assertNull($this->testedConfig->get(0, 'test', 'it'));

		/// beware that the cache returns '!<unset>!' and not null for a non existing value
		$this->assertNull($this->testedConfig->getCache()->get(0, 'test', 'it'));

		// with default value
		$this->assertEquals('default', $this->testedConfig->get(0, 'test', 'it', 'default'));

		// with default value and refresh
		$this->assertEquals('default', $this->testedConfig->get(0, 'test', 'it', 'default', true));
	}

	/**
	 * Test the configuration get() method with refresh
	 *
	 * @dataProvider dataTests
	 */
	public function testGetWithRefresh(int $uid, $data)
	{
		$this->configCache->load($uid, ['test' => ['it' => 'now']]);

		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(PConfigCache::class, $this->testedConfig->getCache());

		// without refresh
		$this->assertEquals('now', $this->testedConfig->get($uid, 'test', 'it'));
		$this->assertEquals('now', $this->testedConfig->getCache()->get($uid, 'test', 'it'));

		// with refresh
		$this->assertEquals($data, $this->testedConfig->get($uid, 'test', 'it', null, true));
		$this->assertEquals($data, $this->testedConfig->getCache()->get($uid, 'test', 'it'));

		// without refresh and wrong value and default
		$this->assertEquals('default', $this->testedConfig->get($uid, 'test', 'not', 'default'));
		$this->assertNull($this->testedConfig->getCache()->get($uid, 'test', 'not'));
	}

	/**
	 * Test the configuration delete() method without a model/db
	 *
	 * @dataProvider dataTests
	 */
	public function testDeleteWithoutDB(int $uid, $data)
	{
		$this->configCache->load($uid, ['test' => ['it' => $data]]);

		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(PConfigCache::class, $this->testedConfig->getCache());

		$this->assertEquals($data, $this->testedConfig->get($uid, 'test', 'it'));
		$this->assertEquals($data, $this->testedConfig->getCache()->get($uid, 'test', 'it'));

		$this->assertTrue($this->testedConfig->delete($uid, 'test', 'it'));
		$this->assertNull($this->testedConfig->get($uid, 'test', 'it'));
		$this->assertNull($this->testedConfig->getCache()->get($uid, 'test', 'it'));

		$this->assertEmpty($this->testedConfig->getCache()->getAll());
	}

	/**
	 * Test the configuration delete() method with a model/db
	 */
	public function testDeleteWithDB()
	{
		$uid = 42;

		$this->configCache->load($uid, ['test' => ['it' => 'now', 'quarter' => 'true']]);

		$this->configModel->shouldReceive('delete')
		                  ->with($uid, 'test', 'it')
		                  ->andReturn(false)
		                  ->once();
		$this->configModel->shouldReceive('delete')
		                  ->with($uid, 'test', 'second')
		                  ->andReturn(true)
		                  ->once();
		$this->configModel->shouldReceive('delete')
		                  ->with($uid, 'test', 'third')
		                  ->andReturn(false)
		                  ->once();
		$this->configModel->shouldReceive('delete')
		                  ->with($uid, 'test', 'quarter')
		                  ->andReturn(true)
		                  ->once();

		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(PConfigCache::class, $this->testedConfig->getCache());

		// directly set the value to the cache
		$this->testedConfig->getCache()->set($uid, 'test', 'it', 'now');

		$this->assertEquals('now', $this->testedConfig->get($uid, 'test', 'it'));
		$this->assertEquals('now', $this->testedConfig->getCache()->get($uid, 'test', 'it'));

		// delete from cache only
		$this->assertTrue($this->testedConfig->delete($uid, 'test', 'it'));
		// delete from db only
		$this->assertTrue($this->testedConfig->delete($uid, 'test', 'second'));
		// no delete
		$this->assertFalse($this->testedConfig->delete($uid, 'test', 'third'));
		// delete both
		$this->assertTrue($this->testedConfig->delete($uid, 'test', 'quarter'));

		$this->assertEmpty($this->testedConfig->getCache()->getAll());
	}

	public function dataMultiUid()
	{
		return [
			'normal' => [
				'data1' => [
					'uid'  => 1,
					'data' => [
						'cat1' => [
							'key1' => 'value1',
						],
						'cat2' => [
							'key2' => 'value2',
						]
					],
				],
				'data2' => [
					'uid' => 2,
					'data' => [
						'cat1' => [
							'key1' => 'value1a',
						],
						'cat2' => [
							'key2' => 'value2',
						],
					],
				],
			],
		];
	}

	/**
	 * Test if multiple uids for caching are usable without errors
	 * @dataProvider dataMultiUid
	 */
	public function testMultipleUidsWithCache(array $data1, array $data2)
	{
		$this->configCache->load($data1['uid'], $data1['data']);
		$this->configCache->load($data2['uid'], $data2['data']);

		$this->testedConfig = $this->getInstance();
		$this->assertInstanceOf(PConfigCache::class, $this->testedConfig->getCache());

		$this->assertConfig($data1['uid'], 'cat1', $data1['data']['cat1']);
		$this->assertConfig($data1['uid'], 'cat2', $data1['data']['cat2']);
		$this->assertConfig($data2['uid'], 'cat1', $data2['data']['cat1']);
		$this->assertConfig($data2['uid'], 'cat2', $data2['data']['cat2']);
	}

	/**
	 * Test when using an invalid UID
	 * @todo check it the clean way before using the config class
	 */
	public function testInvalidUid()
	{
		// bad UID!
		$uid = 0;

		$this->testedConfig = $this->getInstance();

		$this->assertNull($this->testedConfig->get($uid, 'cat1', 'cat2'));
		$this->assertEquals('fallback!', $this->testedConfig->get($uid, 'cat1', 'cat2', 'fallback!'));

		$this->assertFalse($this->testedConfig->set($uid, 'cat1', 'key1', 'doesn\'t matter!'));
		$this->assertFalse($this->testedConfig->delete($uid, 'cat1', 'key1'));
	}
}
