<?php

namespace Friendica\Test\src\Util\Logger;

use Friendica\Test\MockedTest;
use Friendica\Util\Logger\WorkerLogger;
use Psr\Log\LoggerInterface;

class WorkerLoggerTest extends MockedTest
{
	private function assertUid($uid, $length = 7)
	{
		$this->assertRegExp('/^[a-zA-Z0-9]{' . $length . '}+$/', $uid);
	}

	/**
	 * Test the a id with length zero
	 * @expectedException \Error
	 */
	public function testGetWorkerIdZero()
	{
		$logger = \Mockery::mock(LoggerInterface::class);
		new WorkerLogger($logger, 'test', 0);
	}

	/**
	 * Test the generated Uid
	 */
	public function testGetWorkerId()
	{
		$logger = \Mockery::mock(LoggerInterface::class);
		for ($i = 1; $i < 14; $i++) {
			$workLogger = new WorkerLogger($logger, 'test', $i);
			$uid = $workLogger->getWorkerId();
			$this->assertUid($uid, $i);
		}
	}

	public function dataTest()
	{
		return [
			'info' => [
				'func' => 'info',
				'msg' => 'the alert',
				'context' => [],
			],
			'alert' => [
				'func' => 'alert',
				'msg' => 'another alert',
				'context' => ['test' => 'it'],
			],
			'critical' => [
				'func' => 'critical',
				'msg' => 'Critical msg used',
				'context' => ['test' => 'it', 'more' => 0.24545],
			],
			'error' => [
				'func' => 'error',
				'msg' => 21345623,
				'context' => ['test' => 'it', 'yet' => true],
			],
			'warning' => [
				'func' => 'warning',
				'msg' => 'another alert' . 123523 . 324.54534 . 'test',
				'context' => ['test' => 'it', 2 => 'nope'],
			],
			'notice' => [
				'func' => 'notice',
				'msg' => 'Notice' . ' alert' . true . 'with' . '\'strange\'' . 1.24. 'behavior',
				'context' => ['test' => 'it'],
			],
			'debug' => [
				'func' => 'debug',
				'msg' => 'at last a debug',
				'context' => ['test' => 'it'],
			],
		];
	}

	/**
	 * Test the WorkerLogger with different log calls
	 * @dataProvider dataTest
	 */
	public function testEmergency($func, $msg, $context = [])
	{
		$logger = \Mockery::mock(LoggerInterface::class);
		$workLogger = new WorkerLogger($logger, 'test');
		$testContext = $context;
		$testContext['worker_id'] = $workLogger->getWorkerId();
		$testContext['worker_cmd'] = 'test';
		$this->assertUid($testContext['worker_id']);
		$logger
			->shouldReceive($func)
			->with($msg, $testContext)
			->once();
		$workLogger->$func($msg, $context);
	}

	/**
	 * Test the WorkerLogger with
	 */
	public function testLog()
	{
		$logger = \Mockery::mock(LoggerInterface::class);
		$workLogger = new WorkerLogger($logger, 'test');
		$context = $testContext = ['test' => 'it'];
		$testContext['worker_id'] = $workLogger->getWorkerId();
		$testContext['worker_cmd'] = 'test';
		$this->assertUid($testContext['worker_id']);
		$logger
			->shouldReceive('log')
			->with('debug', 'a test', $testContext)
			->once();
		$workLogger->log('debug', 'a test', $context);
	}
}
