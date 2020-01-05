<?php

namespace Friendica\Test\src\Model\Storage;

use Friendica\Core\L10n\L10n;
use Friendica\Factory\ConfigFactory;
use Friendica\Model\Storage\Database;
use Friendica\Model\Storage\IStorage;
use Friendica\Test\DatabaseTestTrait;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\ConfigFileLoader;
use Friendica\Util\Profiler;
use Mockery\MockInterface;
use Psr\Log\NullLogger;

class DatabaseStorageTest extends StorageTest
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

		/** @var MockInterface|L10n $l10n */
		$l10n = \Mockery::mock(L10n::class)->makePartial();

		return new Database($dba, $logger, $l10n);
	}

	protected function assertOption(IStorage $storage)
	{
		$this->assertEmpty($storage->getOptions());
	}
}
