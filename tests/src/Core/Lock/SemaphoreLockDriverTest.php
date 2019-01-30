<?php

namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Lock\SemaphoreLockDriver;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SemaphoreLockDriverTest extends LockTest
{
	public function setUp()
	{
		parent::setUp();

		$this->app->shouldReceive('getHostname')->andReturn('friendica.local');
		$this->mockConfigGet('system', 'temppath', '/tmp/');
	}

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
