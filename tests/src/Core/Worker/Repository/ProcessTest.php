<?php

namespace Friendica\Test\src\Core\Worker\Repository;

use Friendica\Core\Worker\Factory;
use Friendica\Core\Worker\Repository;
use Friendica\DI;
use Friendica\Test\FixtureTest;
use Psr\Log\NullLogger;

class ProcessTest extends FixtureTest
{
	public function testStandardProcess()
	{
		$factory    = new Factory\Process(new NullLogger());
		$repository = new Repository\Process(DI::dba(), new NullLogger(), $factory, []);

		$entityNew = $repository->create(getmypid(), 'test');

		self::assertEquals(getmypid(), $entityNew->pid);
		self::assertEquals('test', $entityNew->command);
		self::assertLessThanOrEqual(new \DateTime('now', new \DateTimeZone('UTC')), $entityNew->created);
		self::assertEquals(php_uname('n'), $entityNew->hostname);
	}

	public function testHostnameEnv()
	{
		$factory    = new Factory\Process(new NullLogger());
		$repository = new Repository\Process(DI::dba(), new NullLogger(), $factory, [Repository\Process::NODE_ENV => 'test_node']);

		$entityNew = $repository->create(getmypid(), 'test');

		self::assertEquals(getmypid(), $entityNew->pid);
		self::assertEquals('test', $entityNew->command);
		self::assertLessThanOrEqual(new \DateTime('now', new \DateTimeZone('UTC')), $entityNew->created);
		self::assertEquals('test_node', $entityNew->hostname);
	}
}
