<?php

namespace Friendica\Test\src\Util\Logger;

use Friendica\Test\MockedTest;
use Friendica\Util\Logger\VoidLogger;
use Psr\Log\LogLevel;

class VoidLoggerTest extends MockedTest
{
	use LoggerDataTrait;

	/**
	 * Test if the profiler is profiling data
	 * @dataProvider dataTests
	 */
	public function testNormal($function, $message, array $context)
	{
		$logger = new VoidLogger();
		$logger->$function($message, $context);
	}

	/**
	 * Test the log() function
	 */
	public function testProfilingLog()
	{
		$logger = new VoidLogger();
		$logger->log(LogLevel::WARNING, 'test', ['a' => 'context']);
	}
}
