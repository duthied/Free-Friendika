<?php


namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\RedisCache;
use Friendica\Core\Config\Configuration;
use Friendica\Core\Lock\CacheLock;

/**
 * @requires extension redis
 */
class RedisCacheLockTest extends LockTest
{
	protected function getInstance()
	{
		$configMock = \Mockery::mock(Configuration::class);

		$configMock
			->shouldReceive('get')
			->with('system', 'redis_host')
			->andReturn('localhost');
		$configMock
			->shouldReceive('get')
			->with('system', 'redis_port')
			->andReturn(null);

		$configMock
			->shouldReceive('get')
			->with('system', 'redis_db', 0)
			->andReturn(3);
		$configMock
			->shouldReceive('get')
			->with('system', 'redis_password')
			->andReturn(null);

		return new CacheLock(new RedisCache('localhost', $configMock));
	}
}
