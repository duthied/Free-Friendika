<?php


namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\MemcachedCache;
use Friendica\Core\Config\Configuration;
use Friendica\Core\Lock\CacheLockDriver;
use Psr\Log\NullLogger;

/**
 * @requires extension memcached
 */
class MemcachedCacheLockTest extends LockTest
{
	protected function getInstance()
	{
		$configMock = \Mockery::mock(Configuration::class);

		$configMock
			->shouldReceive('get')
			->with('system', 'memcached_hosts')
			->andReturn([0 => 'localhost, 11211']);

		$logger = new NullLogger();

		return new CacheLockDriver(new MemcachedCache('localhost', $configMock, $logger));
	}
}
