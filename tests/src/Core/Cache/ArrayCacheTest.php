<?php

namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\ArrayCache;

class ArrayCacheTest extends MemoryCacheTest
{
	protected function getInstance()
	{
		$this->cache = new ArrayCache('localhost');
		return $this->cache;
	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}

	public function testTTL()
	{
		// Array Cache doesn't support TTL
		return true;
	}
}
