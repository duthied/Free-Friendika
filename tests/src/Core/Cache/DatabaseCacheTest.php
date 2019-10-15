<?php

namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache;
use Friendica\Factory\ConfigFactory;
use Friendica\Test\DatabaseTestTrait;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\ConfigFileLoader;
use Friendica\Util\Profiler;
use Psr\Log\NullLogger;

class DatabaseCacheTest extends CacheTest
{
	use DatabaseTestTrait;
	use VFSTrait;

	protected function setUp()
	{
		$this->setUpVfsDir();

		parent::setUp();
	}

	protected function getInstance()
	{
		$logger = new NullLogger();
		$profiler = \Mockery::mock(Profiler::class);
		$profiler->shouldReceive('saveTimestamp')->withAnyArgs()->andReturn(true);

		// load real config to avoid mocking every config-entry which is related to the Database class
		$configFactory = new ConfigFactory();
		$loader = new ConfigFileLoader($this->root->url());
		$configCache = $configFactory->createCache($loader);

		$dba = new StaticDatabase($configCache, $profiler, $logger);

		$this->cache = new Cache\DatabaseCache('database', $dba);
		return $this->cache;
	}

	public function tearDown()
	{
		$this->cache->clear(false);
		parent::tearDown();
	}
}
