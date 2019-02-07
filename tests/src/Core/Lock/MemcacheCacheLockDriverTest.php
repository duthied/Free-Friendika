<?php


namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\CacheDriverFactory;
use Friendica\Core\Lock\CacheLockDriver;

/**
 * @requires extension Memcache
 */
class MemcacheCacheLockDriverTest extends LockTest
{
	protected function getInstance()
	{
		$this->configCache
			->shouldReceive('get')
			->with('system', 'memcache_host', NULL)
			->andReturn('localhost');

		$this->configCache
			->shouldReceive('get')
			->with('system', 'memcache_port', NULL)
			->andReturn(11211);

		return new CacheLockDriver(CacheDriverFactory::create('memcache'));
	}
}
