<?php

namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\APCuCache;

class APCuCacheDriverTest extends MemoryCacheTest
{
	protected function setUp()
	{
		if (!APCuCache::isAvailable()) {
			$this->markTestSkipped('APCu is not available');
		}

		parent::setUp();
	}

	protected function getInstance()
	{
		$this->cache = new APCuCache('localhost');
		return $this->cache;
	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
