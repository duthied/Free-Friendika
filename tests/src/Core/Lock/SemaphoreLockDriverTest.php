<?php

namespace Friendica\Test\src\Core\Lock;

use Dice\Dice;
use Friendica\App;
use Friendica\BaseObject;
use Friendica\Core\Config\Configuration;
use Friendica\Core\Lock\SemaphoreLockDriver;

class SemaphoreLockDriverTest extends LockTest
{
	public function setUp()
	{
		parent::setUp();

		$dice = \Mockery::mock(Dice::class)->makePartial();

		$app = \Mockery::mock(App::class);
		$app->shouldReceive('getHostname')->andReturn('friendica.local');
		$dice->shouldReceive('create')->with(App::class)->andReturn($app);

		$configMock = \Mockery::mock(Configuration::class);
		$configMock
			->shouldReceive('get')
			->with('system', 'temppath', NULL, false)
			->andReturn('/tmp/');
		$dice->shouldReceive('create')->with(Configuration::class)->andReturn($configMock);

		// @todo Because "get_temppath()" is using static methods, we have to initialize the BaseObject
		BaseObject::setDependencyInjection($dice);
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
