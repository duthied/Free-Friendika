<?php

namespace Friendica\Test\src\Core\Lock;


use Friendica\Core\Cache\APCuCache;
use Friendica\Core\Lock\CacheLockDriver;

class APCuCacheLockDriverTest extends LockTest
{
	protected function getInstance()
	{
		return new CacheLockDriver(new APCuCache());
	}
}
