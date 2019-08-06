<?php

namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Lock\DatabaseLock;
use Friendica\Factory\ConfigFactory;
use Friendica\Test\DatabaseTestTrait;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\ConfigFileLoader;
use Friendica\Util\Profiler;
use Psr\Log\NullLogger;

class DatabaseLockDriverTest extends LockTest
{
	use VFSTrait;
	use DatabaseTestTrait;

	protected $pid = 123;

	protected function setUp()
	{
		$this->setUpVfsDir();

		parent::setUp();
	}

	protected function getInstance()
	{
		$logger   = new NullLogger();
		$profiler = \Mockery::mock(Profiler::class);
		$profiler->shouldReceive('saveTimestamp')->withAnyArgs()->andReturn(true);

		// load real config to avoid mocking every config-entry which is related to the Database class
		$configFactory = new ConfigFactory();
		$loader        = new ConfigFileLoader($this->root->url());
		$configCache   = $configFactory->createCache($loader);

		$dba = new StaticDatabase($configCache, $profiler, $logger);

		return new DatabaseLock($dba, $this->pid);
	}
}
