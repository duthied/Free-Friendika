<?php


namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\MemcachedCache;
use Friendica\Core\Config\Configuration;
use Psr\Log\NullLogger;

/**
 * @requires extension memcached
 */
class MemcachedCacheTest extends MemoryCacheTest
{
	protected function getInstance()
	{
		$configMock = \Mockery::mock(Configuration::class);

		$host = $_SERVER['MEMCACHED_HOST'] ?? 'localhost';

		$configMock
			->shouldReceive('get')
			->with('system', 'memcached_hosts')
			->andReturn([0 => $host . ', 11211']);

		$logger = new NullLogger();

		try {
			$this->cache = new MemcachedCache($host, $configMock, $logger);
		} catch (\Exception $exception) {
			$this->markTestSkipped('Memcached is not available');
		}
		return $this->cache;
	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
