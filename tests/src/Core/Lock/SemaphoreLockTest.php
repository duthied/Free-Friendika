<?php

namespace Friendica\Test\src\Core\Lock;

use Dice\Dice;
use Friendica\App;
use Friendica\BaseObject;
use Friendica\Core\Config\Configuration;
use Friendica\Core\Lock\SemaphoreLock;

class SemaphoreLockTest extends LockTest
{
	public function setUp()
	{
		$dice = \Mockery::mock(Dice::class)->makePartial();

		$app = \Mockery::mock(App::class);
		$app->shouldReceive('getHostname')->andReturn('friendica.local');
		$dice->shouldReceive('create')->with(App::class)->andReturn($app);

		$configMock = \Mockery::mock(Configuration::class);
		$configMock
			->shouldReceive('get')
			->with('system', 'temppath', NULL, false)
			->andReturn('/tmp');
		$dice->shouldReceive('create')->with(Configuration::class)->andReturn($configMock);

		// @todo Because "get_temppath()" is using static methods, we have to initialize the BaseObject
		BaseObject::setDependencyInjection($dice);

		parent::setUp();
	}

	protected function getInstance()
	{
		return new SemaphoreLock();
	}

	function testLockTTL()
	{
		// Semaphore doesn't work with TTL
		return true;
	}

	/**
	 * Test if semaphore locking works even for
	 */
	public function testMissingFileNotOverriding()
	{
		$file = get_temppath() . '/test.sem';

		$this->assertTrue(file_exists($file));
		$this->assertFalse($this->instance->releaseLock('test', false));
		$this->assertTrue(file_exists($file));
	}

	/**
	 * Test overriding semaphore release with already set semaphore
	 * This test proves that semaphore locks cannot get released by other instances except themselves
	 *
	 * Check for Bug https://github.com/friendica/friendica/issues/7298#issuecomment-521996540
	 * @see https://github.com/friendica/friendica/issues/7298#issuecomment-521996540
	 */
	public function testMissingFileOverriding()
	{
		$file = get_temppath() . '/test.sem';

		$this->assertTrue(file_exists($file));
		$this->assertFalse($this->instance->releaseLock('test', true));
		$this->assertTrue(file_exists($file));
	}
}
