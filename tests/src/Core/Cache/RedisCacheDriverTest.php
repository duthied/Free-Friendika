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
		$this->configCache
			->shouldReceive('get')
			->with('system', 'redis_host', NULL)
			->andReturn('localhost');

		$this->configCache
			->shouldReceive('get')
			->with('system', 'redis_port', NULL)
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
