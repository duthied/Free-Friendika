<?php

namespace Friendica\Test\src\Util\Logger;

use Friendica\Util\Logger\SyslogLogger;
use Psr\Log\LogLevel;

class SyslogLoggerTest extends AbstractLoggerTest
{
	/**
	 * @var SyslogLoggerWrapper
	 */
	private $logger;

	protected function setUp()
	{
		parent::setUp();

		$this->introspection->shouldReceive('addClasses')->with([SyslogLogger::class]);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getContent()
	{
		return $this->logger->getContent();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getInstance($level = LogLevel::DEBUG)
	{
		$this->logger = new SyslogLoggerWrapper('test', $this->introspection, $level);

		return $this->logger;
	}


	/**
	 * Test when the minimum level is not valid
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessageRegExp /The level ".*" is not valid./
	 */
	public function testWrongMinimumLevel()
	{
		$logger = new SyslogLoggerWrapper('test', $this->introspection, 'NOPE');
	}

	/**
	 * Test when the minimum level is not valid
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessageRegExp /The level ".*" is not valid./
	 */
	public function testWrongLogLevel()
	{
		$logger = new SyslogLoggerWrapper('test', $this->introspection);

		$logger->log('NOPE', 'a test');
	}

	/**
	 * Test when the logfacility is wrong (string)
	 * @expectedException \UnexpectedValueException
	 * @expectedExceptionMessageRegExp /Can\'t open syslog for ident ".*" and facility ".*": .* /
	 */
	public function testServerException()
	{
		$logger = new SyslogLoggerWrapper('test', $this->introspection, LogLevel::DEBUG, null, 'a string');
		$logger->emergency('not working');
	}

	/**
	 * Test the close() method
	 */
	public function testClose()
	{
		$logger = new SyslogLoggerWrapper('test', $this->introspection);
		$logger->emergency('test');
		$logger->close();
		// Reopened itself
		$logger->emergency('test');
	}
}
