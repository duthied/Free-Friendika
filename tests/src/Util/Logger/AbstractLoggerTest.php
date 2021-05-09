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

	protected function setUp(): void
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
		self::assertRegExp(self::LOGLINE, $string);
	}

	public function assertLoglineNums($assertNum, $string)
	{
		self::assertEquals($assertNum, preg_match_all(self::LOGLINE, $string));
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
		self::assertLogline($text);
		self::assertLoglineNums(4, $text);
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
		self::assertContains('A working test', $text);
		self::assertContains('An ["it","is","working"] test', $text);
	}

	/**
	 * Test if a log entry contains all necessary information
	 */
	public function testContainsInformation()
	{
		$logger = $this->getInstance();
		$logger->emergency('A test');

		$text = $this->getContent();
		self::assertContains('"file":"' . self::FILE . '"', $text);
		self::assertContains('"line":' . self::LINE, $text);
		self::assertContains('"function":"' . self::FUNC . '"', $text);
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

		self::assertLoglineNums(5, $text);
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

		self::assertLogline($text);

		self::assertContains(@json_encode($context), $text);
	}

	/**
	 * Test a message with an exception
	 */
	public function testExceptionHandling()
	{
		$e = new \Exception("Test String", 123);
		$eFollowUp = new \Exception("FollowUp", 456, $e);

		$assertion = $eFollowUp->__toString();

		$logger = $this->getInstance();
		$logger->alert('test', ['e' => $eFollowUp]);
		$text = $this->getContent();

		self::assertLogline($text);

		self::assertContains(@json_encode($assertion), $this->getContent());
	}

	public function testNoObjectHandling()
	{
		$logger = $this->getInstance();
		$logger->alert('test', ['e' => ['test' => 'test']]);
		$text = $this->getContent();

		self::assertLogline($text);

		self::assertContains('test', $this->getContent());
	}
}
