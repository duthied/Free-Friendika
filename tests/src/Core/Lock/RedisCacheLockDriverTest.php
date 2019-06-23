<?php


namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Lock\CacheLockDriver;
use Friendica\Factory\CacheDriverFactory;

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

		$this->configMock
			->shouldReceive('get')
			->with('system', 'redis_db')
			->andReturn(3);

		$this->configMock
			->shouldReceive('get')
			->with('system', 'redis_password')
			->andReturn(null);

		return new CacheLockDriver(CacheDriverFactory::create('redis'));
	}
}
