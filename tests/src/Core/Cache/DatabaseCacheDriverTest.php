<?php

namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\CacheDriverFactory;

class DatabaseCacheDriverTest extends CacheTest
{
	protected function getInstance()
	{
		$this->cache = CacheDriverFactory::create('database');
		return $this->cache;
	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
