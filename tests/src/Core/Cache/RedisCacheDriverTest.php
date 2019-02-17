<?php


namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\CacheDriverFactory;

/**
 * @requires extension redis
 */
class RedisCacheDriverTest extends MemoryCacheTest
{
	protected function getInstance()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'redis_host')
			->andReturn('localhost');

		$this->configMock
			->shouldReceive('get')
			->with('system', 'redis_port')
			->andReturn(null);

		$this->cache = CacheDriverFactory::create('redis');
		return $this->cache;
	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
