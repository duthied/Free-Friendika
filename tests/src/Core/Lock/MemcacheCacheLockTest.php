<?php


namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\MemcacheCache;
use Friendica\Core\Config\Configuration;
use Friendica\Core\Lock\CacheLockDriver;

/**
 * @requires extension Memcache
 */
class MemcacheCacheLockTest extends LockTest
{
	protected function getInstance()
	{
		$configMock = \Mockery::mock(Configuration::class);

		$configMock
			->shouldReceive('get')
			->with('system', 'memcache_host')
			->andReturn('localhost');
		$configMock
			->shouldReceive('get')
			->with('system', 'memcache_port')
			->andReturn(11211);

		return new CacheLockDriver(new MemcacheCache('localhost', $configMock));
	}
}
