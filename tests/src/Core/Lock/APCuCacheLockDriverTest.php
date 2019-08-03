<?php

namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\APCuCache;
use Friendica\Core\Lock\CacheLockDriver;

class APCuCacheLockDriverTest extends LockTest
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
		return new CacheLockDriver(new APCuCache('localhost'));
	}
}
