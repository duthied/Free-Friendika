<?php

namespace Friendica\Test\src\Core\Cache;


use Friendica\Core\Cache\ArrayCache;

class ArrayCacheDriverTest extends CacheTest
{
	/**
	 * @var \Friendica\Core\Cache\IMemoryCacheDriver
	 */
	private $cache;

	protected function getInstance()
	{
		$this->cache = new ArrayCache();
		return $this->cache;
	}

	public function tearDown()
	{
		$this->cache->clear();
		parent::tearDown();
	}

	public function testTTL()
	{
		// Array Cache doesn't support TTL
		return true;
	}
}
