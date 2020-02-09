<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Core\Lock;

use Dice\Dice;
use Friendica\App;
use Friendica\Core\Config\IConfig;
use Friendica\Core\Config\JitConfig;
use Friendica\Core\Lock\SemaphoreLock;
use Friendica\DI;
use Mockery\MockInterface;

class SemaphoreLockTest extends LockTest
{
	public function setUp()
	{
		/** @var MockInterface|Dice $dice */
		$dice = \Mockery::mock(Dice::class)->makePartial();

		$app = \Mockery::mock(App::class);
		$app->shouldReceive('getHostname')->andReturn('friendica.local');
		$dice->shouldReceive('create')->with(App::class)->andReturn($app);

		$configMock = \Mockery::mock(JitConfig::class);
		$configMock
			->shouldReceive('get')
			->with('system', 'temppath')
			->andReturn('/tmp/');
		$dice->shouldReceive('create')->with(IConfig::class)->andReturn($configMock);

		// @todo Because "get_temppath()" is using static methods, we have to initialize the BaseObject
		DI::init($dice);

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
	 * Test if semaphore locking works even when trying to release locks, where the file exists
	 * but it shouldn't harm locking
	 */
	public function testMissingFileNotOverriding()
	{
		$file = get_temppath() . '/test.sem';
		touch($file);

		$this->assertTrue(file_exists($file));
		$this->assertFalse($this->instance->release('test', false));
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
		touch($file);

		$this->assertTrue(file_exists($file));
		$this->assertFalse($this->instance->release('test', true));
		$this->assertTrue(file_exists($file));
	}

	/**
	 * Test acquire lock even the semaphore file exists, but isn't used
	 */
	public function testOverrideSemFile()
	{
		$file = get_temppath() . '/test.sem';
		touch($file);

		$this->assertTrue(file_exists($file));
		$this->assertTrue($this->instance->acquire('test'));
		$this->assertTrue($this->instance->isLocked('test'));
		$this->assertTrue($this->instance->release('test'));
	}
}
