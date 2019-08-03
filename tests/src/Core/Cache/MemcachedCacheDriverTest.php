<?php


namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\MemcachedCacheDriver;
use Friendica\Core\Config\Configuration;
use Psr\Log\NullLogger;

/**
 * @requires extension memcached
 */
class MemcachedCacheDriverTest extends MemoryCacheTest
{
	protected function getInstance()
	{
		$configMock = \Mockery::mock(Configuration::class);

		$configMock
			->shouldReceive('get')
			->with('system', 'memcached_hosts')
			->andReturn([0 => 'localhost, 11211']);

		$logger = new NullLogger();

		$this->cache = new MemcachedCacheDriver('localhost', $configMock, $logger);
		return $this->cache;
	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
