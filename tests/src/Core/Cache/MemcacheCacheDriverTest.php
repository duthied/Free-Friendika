<?php


namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\CacheDriverFactory;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @requires extension memcache
 */
class MemcacheCacheDriverTest extends MemoryCacheTest
{
	protected function getInstance()
	{
		$this->mockConfigGet('system', 'memcache_host', 'localhost', 1);
		$this->mockConfigGet('system', 'memcache_port', 11211, 1);

		$this->cache = CacheDriverFactory::create('memcache');
		return $this->cache;

	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
