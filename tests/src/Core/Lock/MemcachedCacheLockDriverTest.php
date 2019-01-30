<?php


namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\CacheDriverFactory;
use Friendica\Core\Lock\CacheLockDriver;

/**
 * @requires extension memcached
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MemcachedCacheLockDriverTest extends LockTest
{
	protected function getInstance()
	{
		$this->mockConfigGet('system', 'memcached_hosts', [0 => 'localhost, 11211']);

		return new CacheLockDriver(CacheDriverFactory::create('memcached'));
	}
}
