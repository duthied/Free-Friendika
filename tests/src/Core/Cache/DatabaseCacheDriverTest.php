<?php

namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache;
use Friendica\Core\Cache\CacheDriverFactory;
use Friendica\Test\Util\DbaCacheMockTrait;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DatabaseCacheDriverTest extends CacheTest
{
	use DbaCacheMockTrait;

	public function setUp()
	{
		$this->mockUtcNow($this->startTime);

		$this->mockConnected();
		$this->mockConnect();

		// The first "clear" at setup
		$this->mockClear(false, true, 2);

		parent::setUp();
	}

	protected function getInstance()
	{
		$this->cache = CacheDriverFactory::create('database');
		return $this->cache;
	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}

	/**
	 * {@inheritdoc}
	 * @dataProvider dataSimple
	 */
	public function testSimple($value1, $value2)
	{
		// assertNull
		$this->mockGet('value1', null, $this->startTime, 1);

		// assertEquals
		$this->mockSet('value1', $value1, Cache::FIVE_MINUTES, $this->startTime, true, 1);
		$this->mockGet('value1', $value1, $this->startTime, 1);

		// assertEquals
		$this->mockSet('value1', $value2, Cache::FIVE_MINUTES, $this->startTime, true, 1);
		$this->mockGet('value1', $value2, $this->startTime, 1);

		// assertEquals
		$this->mockSet('value2', $value1, Cache::FIVE_MINUTES, $this->startTime, true, 1);
		$this->mockGet('value2', $value1, $this->startTime, 1);

		// assertNull
		$this->mockGet('not_set', null, $this->startTime, 1);

		// assertNull
		$this->mockDelete('value1', true, 1);
		$this->mockGet('value1', null, $this->startTime, 1);

		parent::testSimple($value1, $value2);
	}

	/**
	 * {@inheritdoc}
	 * @dataProvider dataSimple
	 */
	public function testClear($value1, $value2, $value3, $value4)
	{
		// assert Equals
		$this->mockSet('1_value1', $value1, Cache::FIVE_MINUTES, $this->startTime, true, 1);
		$this->mockSet('1_value2', $value2, Cache::FIVE_MINUTES, $this->startTime, true, 1);
		$this->mockSet('2_value1', $value3, Cache::FIVE_MINUTES, $this->startTime, true, 1);
		$this->mockSet('3_value1', $value4, Cache::FIVE_MINUTES, $this->startTime, true, 1);

		$this->mockGet('1_value1', $value1, $this->startTime, 2);
		$this->mockGet('1_value2', $value2, $this->startTime, 2);
		$this->mockGet('2_value1', $value3, $this->startTime, 2);
		$this->mockGet('3_value1', $value4, $this->startTime, 2);

		// assertTrue
		$this->mockClear(true, true, 1);
		$this->mockClear(false, true, 1);

		// assertEquals
		$this->mockGet('1_value1', null, $this->startTime, 1);
		$this->mockGet('1_value2', null, $this->startTime, 1);
		$this->mockGet('2_value3', null, $this->startTime, 1);
		$this->mockGet('3_value4', null, $this->startTime, 1);

		parent::testClear($value1, $value2, $value3, $value4);
	}

	/**
	 * {@inheritdoc}
	 * @dataProvider dataTypesInCache
	 */
	public function testDifferentTypesInCache($data)
	{
		$this->mockSet('val', $data, Cache::FIVE_MINUTES, $this->startTime, true, 1);
		$this->mockGet('val', $data, $this->startTime, 1);

		parent::testDifferentTypesInCache($data);
	}

	/**
	 * {@inheritdoc}
	 * @dataProvider dataSimple
	 */
	public function testGetAllKeys($value1, $value2, $value3)
	{
		$this->mockSet('value1', $value1, Cache::FIVE_MINUTES, $this->startTime, true, 1);
		$this->mockSet('value2', $value2,Cache::FIVE_MINUTES, $this->startTime, true, 1);
		$this->mockSet('test_value3', $value3, Cache::FIVE_MINUTES, $this->startTime, true, 1);

		$result = [
			['k' => 'value1'],
			['k' => 'value2'],
			['k' => 'test_value3'],
		];

		$this->mockGetAllKeys(null, $result, $this->startTime, 1);

		$result = [
			['k' => 'test_value3'],
		];

		$this->mockGetAllKeys('test', $result, $this->startTime, 1);

		parent::testGetAllKeys($value1, $value2, $value3);
	}
}
