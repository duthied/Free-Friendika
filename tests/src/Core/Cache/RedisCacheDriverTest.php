<?php


namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\CacheDriverFactory;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @requires extension redis
 */
class RedisCacheDriverTest extends MemoryCacheTest
{
	protected function getInstance()
	{
		$this->mockConfigGet('system', 'redis_host', 'localhost', 1);
		$this->mockConfigGet('system', 'redis_port', null, 1);

		$this->cache = CacheDriverFactory::create('redis');
		return $this->cache;
	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
