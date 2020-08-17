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
