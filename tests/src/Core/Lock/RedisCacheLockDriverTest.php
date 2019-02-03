<?php


namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\CacheDriverFactory;
use Friendica\Core\Lock\CacheLockDriver;

/**
 * @requires extension redis
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RedisCacheLockDriverTest extends LockTest
{
	protected function getInstance()
	{
		$this->mockConfigGet('system', 'redis_host', 'localhost', 1);
		$this->mockConfigGet('system', 'redis_port', null, 1);

		return new CacheLockDriver(CacheDriverFactory::create('redis'));
	}
}
