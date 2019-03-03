<?php


namespace Friendica\Test\src\Core\Lock;

use Friendica\Factory\CacheDriverFactory;
use Friendica\Core\Lock\CacheLockDriver;

/**
 * @requires extension memcached
 */
class MemcachedCacheLockDriverTest extends LockTest
{
	protected function getInstance()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'memcached_hosts')
			->andReturn([0 => 'localhost, 11211']);

		return new CacheLockDriver(CacheDriverFactory::create('memcached'));
	}
}
