<?php


namespace Friendica\Test\src\Core\Lock;

use Friendica\Factory\CacheDriverFactory;
use Friendica\Core\Lock\CacheLockDriver;

/**
 * @requires extension redis
 */
class RedisCacheLockDriverTest extends LockTest
{
	protected function getInstance()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'redis_host')
			->andReturn('localhost');

		$this->configMock
			->shouldReceive('get')
			->with('system', 'redis_port')
			->andReturn(null);

		return new CacheLockDriver(CacheDriverFactory::create('redis'));
	}
}
