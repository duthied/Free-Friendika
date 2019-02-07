<?php

namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Lock\SemaphoreLockDriver;

class SemaphoreLockDriverTest extends LockTest
{
	public function setUp()
	{
		parent::setUp();

		$this->app->shouldReceive('getHostname')->andReturn('friendica.local');

		$this->configCache
			->shouldReceive('get')
			->with('system', 'temppath', NULL)
			->andReturn('/tmp/');
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
