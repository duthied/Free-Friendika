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
		return new CacheLockDriver(CacheDriverFactory::create('memcached'));
	}
}
