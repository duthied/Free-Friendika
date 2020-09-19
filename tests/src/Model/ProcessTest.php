<?php

namespace Friendica\Test\src\Model;

use Friendica\Factory\ConfigFactory;
use Friendica\Model\Process;
use Friendica\Test\DatabaseTest;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\ConfigFileLoader;
use Friendica\Util\Profiler;
use Psr\Log\NullLogger;

class ProcessTest extends DatabaseTest
{
	use VFSTrait;

	/** @var StaticDatabase */
	private $dba;

	protected function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->logger = new NullLogger();

		$profiler = \Mockery::mock(Profiler::class);
		$profiler->shouldReceive('saveTimestamp')->withAnyArgs()->andReturn(true);

		// load real config to avoid mocking every config-entry which is related to the Database class
		$configFactory = new ConfigFactory();
		$loader        = new ConfigFileLoader($this->root->url());
		$configCache   = $configFactory->createCache($loader);

		$this->dba = new StaticDatabase($configCache, $profiler, $this->logger);
	}

	public function testInsertDelete()
	{
		$process = new Process($this->dba);

		$this->assertEquals(0, $this->dba->count('process'));
		$process->insert('test', 1);
		$process->insert('test2', 2);
		$process->insert('test3', 3);

		$this->assertEquals(3, $this->dba->count('process'));

		$this->assertEquals([
			['command' => 'test']
		], $this->dba->selectToArray('process', ['command'], ['pid' => 1]));

		$process->deleteByPid(1);

		$this->assertEmpty($this->dba->selectToArray('process', ['command'], ['pid' => 1]));

		$this->assertEquals(2, $this->dba->count('process'));
	}

	public function testDoubleInsert()
	{
		$process = new Process($this->dba);

		$process->insert('test', 1);

		// double insert doesn't work
		$process->insert('test23', 1);

		$this->assertEquals([['command' => 'test']], $this->dba->selectToArray('process', ['command'], ['pid' => 1]));
	}

	public function testWrongDelete()
	{
		$process = new Process($this->dba);

		// Just ignore wrong deletes, no execution is thrown
		$process->deleteByPid(-1);
	}
}
