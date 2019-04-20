<?php

namespace Friendica\Test\src\Core\Console;

use Friendica\Core\Console\BlockedServers;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class BlockedServersConsoleTest extends ConsoleTest
{
	protected $defaultBlockList = [
		[
			'domain' => 'social.nobodyhasthe.biz',
			'reason' => 'Illegal content',
		],
		[
			'domain' => 'pod.ordoevangelistarum.com',
			'reason' => 'Illegal content',
		]
	];

	protected function setUp()
	{
		parent::setUp();

		$this->mockApp($this->root);
	}

	/**
	 * Test to list the default blocked servers
	 */
	public function testBlockedServersList()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'blocklist')
			->andReturn($this->defaultBlockList)
			->once();

		$console = new BlockedServers($this->consoleArgv);
		$txt = $this->dumpExecute($console);

		$output = <<<CONS
+----------------------------+-----------------+
| Domain                     | Reason          |
+----------------------------+-----------------+
| social.nobodyhasthe.biz    | Illegal content |
| pod.ordoevangelistarum.com | Illegal content |
+----------------------------+-----------------+


CONS;

		$this->assertEquals($output, $txt);
	}

	/**
	 * Test blockedservers add command
	 */
	public function testAddBlockedServer()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'blocklist')
			->andReturn($this->defaultBlockList)
			->once();

		$newBlockList = $this->defaultBlockList;
		$newBlockList[] = [
			'domain' => 'testme.now',
			'reason' => 'I like it!',
		];

		$this->configMock
			->shouldReceive('set')
			->with('system', 'blocklist', $newBlockList)
			->andReturn(true)
			->once();

		$console = new BlockedServers($this->consoleArgv);
		$console->setArgument(0, 'add');
		$console->setArgument(1, 'testme.now');
		$console->setArgument(2, 'I like it!');
		$txt = $this->dumpExecute($console);

		$this->assertEquals('The domain \'testme.now\' is now blocked. (Reason: \'I like it!\')' . PHP_EOL, $txt);
	}

	/**
	 * Test blockedservers add command with the default reason
	 */
	public function testAddBlockedServerWithDefaultReason()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'blocklist')
			->andReturn($this->defaultBlockList)
			->once();

		$newBlockList = $this->defaultBlockList;
		$newBlockList[] = [
			'domain' => 'testme.now',
			'reason' => BlockedServers::DEFAULT_REASON,
		];

		$this->configMock
			->shouldReceive('set')
			->with('system', 'blocklist', $newBlockList)
			->andReturn(true)
			->once();

		$console = new BlockedServers($this->consoleArgv);
		$console->setArgument(0, 'add');
		$console->setArgument(1, 'testme.now');
		$txt = $this->dumpExecute($console);

		$this->assertEquals('The domain \'testme.now\' is now blocked. (Reason: \'' . BlockedServers::DEFAULT_REASON . '\')' . PHP_EOL, $txt);
	}

	/**
	 * Test blockedservers add command on existed domain
	 */
	public function testUpdateBlockedServer()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'blocklist')
			->andReturn($this->defaultBlockList)
			->once();

		$newBlockList = [
			[
				'domain' => 'social.nobodyhasthe.biz',
				'reason' => 'Illegal content',
			],
			[
				'domain' => 'pod.ordoevangelistarum.com',
				'reason' => 'Other reason',
			]
		];

		$this->configMock
			->shouldReceive('set')
			->with('system', 'blocklist', $newBlockList)
			->andReturn(true)
			->once();

		$console = new BlockedServers($this->consoleArgv);
		$console->setArgument(0, 'add');
		$console->setArgument(1, 'pod.ordoevangelistarum.com');
		$console->setArgument(2, 'Other reason');
		$txt = $this->dumpExecute($console);

		$this->assertEquals('The domain \'pod.ordoevangelistarum.com\' is now updated. (Reason: \'Other reason\')' . PHP_EOL, $txt);
	}

	/**
	 * Test blockedservers remove command
	 */
	public function testRemoveBlockedServer()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'blocklist')
			->andReturn($this->defaultBlockList)
			->once();

		$newBlockList = [
			[
				'domain' => 'social.nobodyhasthe.biz',
				'reason' => 'Illegal content',
			],
		];

		$this->configMock
			->shouldReceive('set')
			->with('system', 'blocklist', $newBlockList)
			->andReturn(true)
			->once();

		$console = new BlockedServers($this->consoleArgv);
		$console->setArgument(0, 'remove');
		$console->setArgument(1, 'pod.ordoevangelistarum.com');
		$txt = $this->dumpExecute($console);

		$this->assertEquals('The domain \'pod.ordoevangelistarum.com\' is not more blocked' . PHP_EOL, $txt);
	}

	/**
	 * Test blockedservers with a wrong command
	 */
	public function testBlockedServersWrongCommand()
	{
		$console = new BlockedServers($this->consoleArgv);
		$console->setArgument(0, 'wrongcommand');
		$txt = $this->dumpExecute($console);

		$this->assertStringStartsWith('[Warning] Unknown command', $txt);
	}

	/**
	 * Test blockedservers remove with not existing domain
	 */
	public function testRemoveBlockedServerNotExist()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'blocklist')
			->andReturn($this->defaultBlockList)
			->once();

		$console = new BlockedServers($this->consoleArgv);
		$console->setArgument(0, 'remove');
		$console->setArgument(1, 'not.exiting');
		$txt = $this->dumpExecute($console);

		$this->assertEquals('The domain \'not.exiting\' is not blocked.' . PHP_EOL, $txt);
	}

	/**
	 * Test blockedservers add command without argument
	 */
	public function testAddBlockedServerMissingArgument()
	{
		$console = new BlockedServers($this->consoleArgv);
		$console->setArgument(0, 'add');
		$txt = $this->dumpExecute($console);

		$this->assertStringStartsWith('[Warning] Add needs a domain and optional a reason.', $txt);
	}

	/**
	 * Test blockedservers add command without save
	 */
	public function testAddBlockedServerNoSave()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'blocklist')
			->andReturn($this->defaultBlockList)
			->once();

		$newBlockList = $this->defaultBlockList;
		$newBlockList[] = [
			'domain' => 'testme.now',
			'reason' => BlockedServers::DEFAULT_REASON,
		];

		$this->configMock
			->shouldReceive('set')
			->with('system', 'blocklist', $newBlockList)
			->andReturn(false)
			->once();

		$console = new BlockedServers($this->consoleArgv);
		$console->setArgument(0, 'add');
		$console->setArgument(1, 'testme.now');
		$txt = $this->dumpExecute($console);

		$this->assertEquals('Couldn\'t save \'testme.now\' as blocked server' . PHP_EOL, $txt);
	}

	/**
	 * Test blockedservers remove command without save
	 */
	public function testRemoveBlockedServerNoSave()
	{
		$this->configMock
			->shouldReceive('get')
			->with('system', 'blocklist')
			->andReturn($this->defaultBlockList)
			->once();

		$newBlockList = [
			[
				'domain' => 'social.nobodyhasthe.biz',
				'reason' => 'Illegal content',
			],
		];

		$this->configMock
			->shouldReceive('set')
			->with('system', 'blocklist', $newBlockList)
			->andReturn(false)
			->once();

		$console = new BlockedServers($this->consoleArgv);
		$console->setArgument(0, 'remove');
		$console->setArgument(1, 'pod.ordoevangelistarum.com');
		$txt = $this->dumpExecute($console);

		$this->assertEquals('Couldn\'t remove \'pod.ordoevangelistarum.com\' from blocked servers' . PHP_EOL, $txt);
	}

	/**
	 * Test blockedservers remove command without argument
	 */
	public function testRemoveBlockedServerMissingArgument()
	{
		$console = new BlockedServers($this->consoleArgv);
		$console->setArgument(0, 'remove');
		$txt = $this->dumpExecute($console);

		$this->assertStringStartsWith('[Warning] Remove needs a second parameter.', $txt);
	}

	/**
	 * Test the blockedservers help
	 */
	public function testBlockedServersHelp()
	{
		$console = new BlockedServers($this->consoleArgv);
		$console->setOption('help', true);
		$txt = $this->dumpExecute($console);

		$help = <<<HELP
console blockedservers - Manage blocked servers
Usage
	bin/console blockedservers [-h|--help|-?] [-v]
	bin/console blockedservers add <server> <reason> [-h|--help|-?] [-v]
	bin/console blockedservers remove <server> [-h|--help|-?] [-v]

Description
	With this tool, you can list the current blocked servers
    or you can add / remove a blocked server from the list

Options
    -h|--help|-? Show help information
    -v           Show more debug information.

HELP;

		$this->assertEquals($help, $txt);
	}
}
