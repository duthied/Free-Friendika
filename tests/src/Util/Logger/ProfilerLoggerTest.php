<?php

namespace Friendica\Test\src\Util\Logger;

use Friendica\Test\MockedTest;
use Friendica\Util\Logger\ProfilerLogger;
use Friendica\Util\Profiler;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class ProfilerLoggerTest extends MockedTest
{
	use LoggerDataTrait;

	/**
	 * @var LoggerInterface|MockInterface
	 */
	private $logger;
	/**
	 * @var Profiler|MockInterface
	 */
	private $profiler;

	protected function setUp()
	{
		parent::setUp();

		$this->logger = \Mockery::mock(LoggerInterface::class);
		$this->profiler = \Mockery::mock(Profiler::class);
	}

	/**
	 * Test if the profiler is profiling data
	 * @dataProvider dataTests
	 */
	public function testProfiling($function, $message, array $context)
	{
		$logger = new ProfilerLogger($this->logger, $this->profiler);

		$this->logger->shouldReceive($function)->with($message, $context)->once();
		$this->profiler->shouldReceive('saveTimestamp')->with(\Mockery::any(), 'file', \Mockery::any())->once();
		$logger->$function($message, $context);
	}

	/**
	 * Test the log() function
	 */
	public function testProfilingLog()
	{
		$logger = new ProfilerLogger($this->logger, $this->profiler);

		$this->logger->shouldReceive('log')->with(LogLevel::WARNING, 'test', ['a' => 'context'])->once();
		$this->profiler->shouldReceive('saveTimestamp')->with(\Mockery::any(), 'file', \Mockery::any())->once();

		$logger->log(LogLevel::WARNING, 'test', ['a' => 'context']);
	}
}
