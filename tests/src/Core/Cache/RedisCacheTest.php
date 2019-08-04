<?php


namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\RedisCache;
use Friendica\Core\Config\Configuration;

/**
 * @requires extension redis
 */
class RedisCacheTest extends MemoryCacheTest
{
	protected function getInstance()
	{
		$configMock = \Mockery::mock(Configuration::class);

		$configMock
			->shouldReceive('get')
			->with('system', 'redis_host')
			->andReturn('localhost');
		$configMock
			->shouldReceive('get')
			->with('system', 'redis_port')
			->andReturn(null);

		$configMock
			->shouldReceive('get')
			->with('system', 'redis_db', 0)
			->andReturn(3);
		$configMock
			->shouldReceive('get')
			->with('system', 'redis_password')
			->andReturn(null);

		$this->cache = new RedisCache('localhost', $configMock);
		return $this->cache;
	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
