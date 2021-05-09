<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Test\src\Util;

use Friendica\Core\Config\Cache;
use Friendica\Core\Config\IConfig;
use Friendica\Test\MockedTest;
use Friendica\Util\Profiler;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class ProfilerTest extends MockedTest
{
	/**
	 * @var LoggerInterface|MockInterface
	 */
	private $logger;

	protected function setUp(): void
	{
		parent::setUp();

		$this->logger = \Mockery::mock(LoggerInterface::class);
	}

	/**
	 * Test the Profiler setup
	 */
	public function testSetUp()
	{
		$configCache = \Mockery::mock(Cache::class);
		$configCache->shouldReceive('get')
		            ->withAnyArgs()
		            ->andReturn(true)
		            ->twice();
		$profiler = new Profiler($configCache);

		self::assertInstanceOf(Profiler::class, $profiler);
	}

	/**
	 * A dataset for different profiling settings
	 * @return array
	 */
	public function dataPerformance()
	{
		return [
			'database' => [
				'timestamp' => time(),
				'name' => 'database',
				'functions' => ['test', 'it'],
			],
			'database_write' => [
				'timestamp' => time(),
				'name' => 'database_write',
				'functions' => ['test', 'it2'],
			],
			'cache' => [
				'timestamp' => time(),
				'name' => 'cache',
				'functions' => ['test', 'it3'],
			],
			'cache_write' => [
				'timestamp' => time(),
				'name' => 'cache_write',
				'functions' => ['test', 'it4'],
			],
			'network' => [
				'timestamp' => time(),
				'name' => 'network',
				'functions' => ['test', 'it5'],
			],
			'file' => [
				'timestamp' => time(),
				'name' => 'file',
				'functions' => [],
			],
			'rendering' => [
				'timestamp' => time(),
				'name' => 'rendering',
				'functions' => ['test', 'it7'],
			],
			'parser' => [
				'timestamp' => time(),
				'name' => 'parser',
				'functions' => ['test', 'it8'],
			],
			'marktime' => [
				'timestamp' => time(),
				'name' => 'parser',
				'functions' => ['test'],
			],
			// This one isn't set during reset
			'unknown' => [
				'timestamp' => time(),
				'name' => 'unknown',
				'functions' => ['test'],
			],
		];
	}

	/**
	 * Test the Profiler savetimestamp
	 * @dataProvider dataPerformance
	 */
	public function testSaveTimestamp($timestamp, $name, array $functions)
	{
		$configCache = \Mockery::mock(Cache::class);
		$configCache->shouldReceive('get')
		            ->withAnyArgs()
		            ->andReturn(true)
		            ->twice();

		$profiler = new Profiler($configCache);

		foreach ($functions as $function) {
			$profiler->saveTimestamp($timestamp, $name, $function);
		}

		self::assertGreaterThanOrEqual(0, $profiler->get($name));
	}

	/**
	 * Test the Profiler reset
	 * @dataProvider dataPerformance
	 */
	public function testReset($timestamp, $name)
	{
		$configCache = \Mockery::mock(Cache::class);
		$configCache->shouldReceive('get')
		            ->withAnyArgs()
		            ->andReturn(true)
		            ->twice();

		$profiler = new Profiler($configCache);

		$profiler->saveTimestamp($timestamp, $name);
		$profiler->reset();

		self::assertEquals(0, $profiler->get($name));
	}

	public function dataBig()
	{
		return [
			'big' => [
				'data' => [
					'database' => [
						'timestamp' => time(),
						'name' => 'database',
						'functions' => ['test', 'it'],
					],
					'database_write' => [
						'timestamp' => time(),
						'name' => 'database_write',
						'functions' => ['test', 'it2'],
					],
					'cache' => [
						'timestamp' => time(),
						'name' => 'cache',
						'functions' => ['test', 'it3'],
					],
					'cache_write' => [
						'timestamp' => time(),
						'name' => 'cache_write',
						'functions' => ['test', 'it4'],
					],
					'network' => [
						'timestamp' => time(),
						'name' => 'network',
						'functions' => ['test', 'it5'],
					],
				]
			]
		];
	}

	/**
	 * Test the output of the Profiler
	 * @dataProvider dataBig
	 */
	public function testSaveLog($data)
	{
		$this->logger
			->shouldReceive('info')
			->with('test', \Mockery::any())
			->once();
		$this->logger
			->shouldReceive('info')
			->once();

		$configCache = \Mockery::mock(Cache::class);
		$configCache->shouldReceive('get')
		            ->withAnyArgs()
		            ->andReturn(true)
		            ->twice();

		$profiler = new Profiler($configCache);

		foreach ($data as $perf => $items) {
			foreach ($items['functions'] as $function) {
				$profiler->saveTimestamp($items['timestamp'], $items['name'], $function);
			}
		}

		$profiler->saveLog($this->logger, 'test');

		$output = $profiler->getRendertimeString();

		foreach ($data as $perf => $items) {
			foreach ($items['functions'] as $function) {
				// assert that the output contains the functions
				self::assertRegExp('/' . $function . ': \d+/', $output);
			}
		}
	}

	/**
	 * Test different enable and disable states of the profiler
	 */
	public function testEnableDisable()
	{
		$configCache = \Mockery::mock(Cache::class);
		$configCache->shouldReceive('get')
		            ->with('system', 'profiler')
		            ->andReturn(true)
		            ->once();
		$configCache->shouldReceive('get')
		            ->with('rendertime', 'callstack')
		            ->andReturn(false)
		            ->once();

		$profiler = new Profiler($configCache);

		self::assertFalse($profiler->isRendertime());
		self::assertEmpty($profiler->getRendertimeString());

		$profiler->saveTimestamp(time(), 'network', 'test1');

		$config = \Mockery::mock(IConfig::class);
		$config->shouldReceive('get')
		            ->with('system', 'profiler')
		            ->andReturn(false)
		            ->once();
		$config->shouldReceive('get')
		            ->with('rendertime', 'callstack')
		            ->andReturn(false)
		            ->once();

		$profiler->update($config);

		self::assertFalse($profiler->isRendertime());
		self::assertEmpty($profiler->getRendertimeString());

		$config->shouldReceive('get')
		       ->with('system', 'profiler')
		       ->andReturn(true)
		       ->once();
		$config->shouldReceive('get')
		       ->with('rendertime', 'callstack')
		       ->andReturn(true)
		       ->once();

		$profiler->update($config);

		$profiler->saveTimestamp(time(), 'database', 'test2');

		self::assertTrue($profiler->isRendertime());
		$output = $profiler->getRendertimeString();
		self::assertRegExp('/test1: \d+/', $output);
		self::assertRegExp('/test2: \d+/', $output);
	}
}
