<?php


namespace Friendica\Test\src\Core\Lock;

use Friendica\Factory\CacheDriverFactory;
use Friendica\Core\Lock\CacheLockDriver;

/**
 * @requires extension Memcache
 */
class MemcacheCacheLockDriverTest extends LockTest
{
	protected function getInstance()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'memcache_host')
			->andReturn('localhost');

		$this->configMock
			->shouldReceive('get')
			->with('system', 'memcache_port')
			->andReturn(11211);

		return new CacheLockDriver(CacheDriverFactory::create('memcache'));
	}
}
