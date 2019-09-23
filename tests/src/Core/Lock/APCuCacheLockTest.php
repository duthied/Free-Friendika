<?php

namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\APCuCache;
use Friendica\Core\Lock\CacheLock;

/**
 * @group APCU
 */
class APCuCacheLockTest extends LockTest
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
		return new CacheLock(new APCuCache('localhost'));
	}
}
