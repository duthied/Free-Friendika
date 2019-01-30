<?php


namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\CacheDriverFactory;
use Friendica\Core\Lock\CacheLockDriver;

/**
 * @requires extension Memcache
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MemcacheCacheLockDriverTest extends LockTest
{
	protected function getInstance()
	{
		$this->mockConfigGet('system', 'memcache_host', 'localhost', 1);
		$this->mockConfigGet('system', 'memcache_port', 11211, 1);

		return new CacheLockDriver(CacheDriverFactory::create('memcache'));
	}
}
