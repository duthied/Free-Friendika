<?php

namespace Friendica\Test\src\Util\Logger;

use Friendica\Test\MockedTest;
use Friendica\Util\Introspection;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

abstract class AbstractLoggerTest extends MockedTest
{
	use LoggerDataTrait;

	const LOGLINE = '/.* \[.*\]: .* \{.*\"file\":\".*\".*,.*\"line\":\d*,.*\"function\":\".*\".*,.*\"uid\":\".*\".*}/';

	const FILE = 'test';
	const LINE = 666;
	const FUNC = 'myfunction';

	/**
	 * @var Introspection|MockInterface
	 */
	protected $introspection;

	/**
	 * Returns the content of the current logger instance
	 *
	 * @return string
	 */
	abstract protected function getContent();

	/**
	 * Returns the current logger instance
	 *
	 * @param string $level the default loglevel
	 *
	 * @return LoggerInterface
	 */
	abstract protected function getInstance($level = LogLevel::DEBUG);

	protected function setUp()
	{
		parent::setUp();

		$this->introspection = \Mockery::mock(Introspection::class);
		$this->introspection->shouldReceive('getRecord')->andReturn([
			'file'     => self::FILE,
			'line'     => self::LINE,
			'function' => self::FUNC
		]);
	}

	public function assertLogline($string)
	{
		$this->assertRegExp(self::LOGLINE, $string);
	}

	public function assertLoglineNums($assertNum, $string)
	{
		$this->assertEquals($assertNum, preg_match_all(self::LOGLINE, $string));
	}

	/**
	 * Test if the logger works correctly
	 */
	public function testNormal()
	{
		$logger = $this->getInstance();
		$logger->emergency('working!');
		$logger->alert('working too!');
		$logger->debug('and now?');
		$logger->notice('message', ['an' => 'context']);

		$text = $this->getContent();
		$this->assertLogline($text);
		$this->assertLoglineNums(4, $text);
	}

	/**
	 * Test if a log entry is correctly interpolated
	 */
	public function testPsrInterpolate()
	{
		$logger = $this->getInstance();

		$logger->emergency('A {psr} test', ['psr' => 'working']);
		$logger->alert('An {array} test', ['array' => ['it', 'is', 'working']]);
		$text = $this->getContent();
		$this->assertContains('A working test', $text);
		$this->assertContains('An ["it","is","working"] test', $text);
	}

	/**
	 * Test if a log entry contains all necessary information
	 */
	public function testContainsInformation()
	{
		$logger = $this->getInstance();
		$logger->emergency('A test');

		$text = $this->getContent();
		$this->assertContains('"file":"' . self::FILE . '"', $text);
		$this->assertContains('"line":' . self::LINE, $text);
		$this->assertContains('"function":"' . self::FUNC . '"', $text);
	}

	/**
	 * Test if the minimum level is working
	 */
	public function testMinimumLevel()
	{
		$logger = $this->getInstance(LogLevel::NOTICE);

		$logger->emergency('working');
		$logger->alert('working');
		$logger->error('working');
		$logger->warning('working');
		$logger->notice('working');
		$logger->info('not working');
		$logger->debug('not working');

		$text = $this->getContent();

		$this->assertLoglineNums(5, $text);
	}

	/**
	 * Test with different logging data
	 * @dataProvider dataTests
	 */
	public function testDifferentTypes($function, $message, array $context)
	{
		$logger = $this->getInstance();
		$logger->$function($message, $context);

		$text = $this->getContent();

		$this->assertLogline($text);

		$this->assertContains(@json_encode($context), $text);
	}
}
