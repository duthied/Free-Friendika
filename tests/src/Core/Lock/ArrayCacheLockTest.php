<?php

namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Cache\ArrayCache;
use Friendica\Core\Lock\CacheLockDriver;

class ArrayCacheLockTest extends LockTest
{
	protected function getInstance()
	{
		return new CacheLockDriver(new ArrayCache('localhost'));
	}

	public function testLockTTL()
	{
		// ArrayCache doesn't support TTL
		return true;
	}
}
