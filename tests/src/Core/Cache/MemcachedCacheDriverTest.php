<?php


namespace Friendica\Test\src\Core\Cache;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
use Friendica\Core\Cache\CacheDriverFactory;

/**
 * @requires extension memcached
 */
class MemcachedCacheDriverTest extends MemoryCacheTest
{
	protected function getInstance()
	{
		$this->cache = CacheDriverFactory::create('memcached');
		return $this->cache;
	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
