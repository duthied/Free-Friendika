<?php

namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Lock\SemaphoreLockDriver;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SemaphoreLockDriverTest extends LockTest
{
	protected function getInstance()
	{
		return new SemaphoreLockDriver();
	}

	function testLockTTL()
	{
		// Semaphore doesn't work with TTL
		return true;
	}
}
