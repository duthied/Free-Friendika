<?php

namespace Friendica\Test\src\Core\Console;

use Friendica\Core\Console\BlockedServers;

/**
 *
 */
class BlockedServerConsoleTest extends ConsoleTest
{
	protected $defaultBlockList =[
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
	public function testBlockedServerList()
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
}
