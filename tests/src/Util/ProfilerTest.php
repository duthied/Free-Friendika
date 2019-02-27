<?php

namespace src\Util;

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

	protected function setUp()
	{
		parent::setUp();

		$this->logger = \Mockery::mock(LoggerInterface::class);
	}

	/**
	 * Test the Profiler setup
	 */
	public function testSetUp()
	{
		$profiler = new Profiler(true, true);
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
		$profiler = new Profiler(true, true);

		foreach ($functions as $function) {
			$profiler->saveTimestamp($timestamp, $name, $function);
		}

		$this->assertGreaterThanOrEqual(0, $profiler->get($name));
	}

	/**
	 * Test the Profiler reset
	 * @dataProvider dataPerformance
	 */
	public function testReset($timestamp, $name, array $functions)
	{
		$profiler = new Profiler(true, true);

		$profiler->saveTimestamp($timestamp, $name);
		$profiler->reset();

		$this->assertEquals(0, $profiler->get($name));
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

		$profiler = new Profiler(true, true);

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
				$this->assertRegExp('/' . $function . ': \d+/', $output);
			}
		}
	}

	/**
	 * Test different enable and disable states of the profiler
	 */
	public function testEnableDisable()
	{
		$profiler = new Profiler(true, false);

		$this->assertFalse($profiler->isRendertime());
		$this->assertEmpty($profiler->getRendertimeString());

		$profiler->saveTimestamp(time(), 'network', 'test1');

		$profiler->update(false, false);

		$this->assertFalse($profiler->isRendertime());
		$this->assertEmpty($profiler->getRendertimeString());

		$profiler->update(true, true);

		$profiler->saveTimestamp(time(), 'database', 'test2');

		$this->assertTrue($profiler->isRendertime());
		$output = $profiler->getRendertimeString();
		$this->assertRegExp('/test1: \d+/', $output);
		$this->assertRegExp('/test2: \d+/', $output);
	}
}
