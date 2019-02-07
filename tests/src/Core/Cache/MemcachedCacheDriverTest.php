<?php


namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\CacheDriverFactory;

/**
 * @requires extension memcached
 */
class MemcachedCacheDriverTest extends MemoryCacheTest
{
	protected function getInstance()
	{
		$this->configCache
			->shouldReceive('get')
			->with('system', 'memcached_hosts', NULL)
			->andReturn([0 => 'localhost, 11211']);

		$this->cache = CacheDriverFactory::create('memcached');
		return $this->cache;
	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
