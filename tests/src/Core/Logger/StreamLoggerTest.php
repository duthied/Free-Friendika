<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Test\src\Core\Logger;

use Friendica\Core\Logger\Exception\LoggerArgumentException;
use Friendica\Core\Logger\Exception\LogLevelException;
use Friendica\Test\Util\VFSTrait;
use Friendica\Core\Logger\Type\StreamLogger;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use Psr\Log\LogLevel;

class StreamLoggerTest extends AbstractLoggerTest
{
	use VFSTrait;

	/**
	 * @var vfsStreamFile
	 */
	private $logfile;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpVfsDir();
	}

	/**
	 * {@@inheritdoc}
	 */
	protected function getInstance($level = LogLevel::DEBUG, $logfile = 'friendica.log')
	{
		$this->logfile = vfsStream::newFile($logfile)
			->at($this->root);

		$this->config->shouldReceive('get')->with('system', 'logfile')->andReturn($this->logfile->url())->once();
		$this->config->shouldReceive('get')->with('system', 'loglevel')->andReturn($level)->once();

		$loggerFactory = new \Friendica\Core\Logger\Factory\StreamLogger($this->introspection, 'test');
		return $loggerFactory->create($this->config);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getContent()
	{
		return $this->logfile->getContent();
	}

	/**
	 * Test when a file isn't set
	 */
	public function testNoUrl()
	{
		$this->expectException(LoggerArgumentException::class);
		$this->expectExceptionMessage(' is not a valid logfile');

		$this->config->shouldReceive('get')->with('system', 'logfile')->andReturn('')->once();

		$loggerFactory = new \Friendica\Core\Logger\Factory\StreamLogger($this->introspection, 'test');
		$logger = $loggerFactory->create($this->config);

		$logger->emergency('not working');
	}

	/**
	 * Test when a file cannot be opened
	 */
	public function testWrongUrl()
	{
		$this->expectException(LoggerArgumentException::class);

		$logfile = vfsStream::newFile('friendica.log')
			->at($this->root)->chmod(0);

		$this->config->shouldReceive('get')->with('system', 'logfile')->andReturn($logfile->url())->once();

		$loggerFactory = new \Friendica\Core\Logger\Factory\StreamLogger($this->introspection, 'test');
		$logger = $loggerFactory->create($this->config);

		$logger->emergency('not working');
	}

	/**
	 * Test when the directory cannot get created
	 */
	public function testWrongDir()
	{
		$this->expectException(\UnexpectedValueException::class);
		$this->expectExceptionMessageMatches("/Directory .* cannot get created: .* /");

		static::markTestIncomplete('We need a platform independent way to set directory to readonly');

		$loggerFactory = new \Friendica\Core\Logger\Factory\StreamLogger($this->introspection, 'test');
		$logger = $loggerFactory->create($this->config);

		$logger->emergency('not working');
	}

	/**
	 * Test when the minimum level is not valid
	 */
	public function testWrongMinimumLevel()
	{
		$this->expectException(LogLevelException::class);
		$this->expectExceptionMessageMatches("/The level \".*\" is not valid./");

		$logger = $this->getInstance('NOPE');
	}

	/**
	 * Test when the minimum level is not valid
	 */
	public function testWrongLogLevel()
	{
		$this->expectException(LogLevelException::class);
		$this->expectExceptionMessageMatches("/The level \".*\" is not valid./");

		$logger = $this->getInstance('NOPE');

		$logger->log('NOPE', 'a test');
	}

	/**
	 * Test a relative path
	 * @doesNotPerformAssertions
	 */
	public function testRealPath()
	{
		static::markTestSkipped('vfsStream isn\'t compatible with chdir, so not testable.');

		$logfile = vfsStream::newFile('friendica.log')
		                    ->at($this->root);

		chdir($this->root->getChild('logs')->url());

		$this->config->shouldReceive('get')->with('system', 'logfile')->andReturn('../friendica.log')->once();

		$logger = new StreamLogger('test', $this->config, $this->introspection, $this->fileSystem);

		$logger->info('Test');
	}
}
