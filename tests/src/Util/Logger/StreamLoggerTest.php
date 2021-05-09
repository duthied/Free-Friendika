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

use Friendica\Util\FileSystem;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\Logger\StreamLogger;
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

	/**
	 * @var Filesystem
	 */
	private $fileSystem;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->fileSystem = new FileSystem();
	}

	/**
	 * {@@inheritdoc}
	 */
	protected function getInstance($level = LogLevel::DEBUG)
	{
		$this->logfile = vfsStream::newFile('friendica.log')
			->at($this->root);

		$logger = new StreamLogger('test', $this->logfile->url(), $this->introspection, $this->fileSystem, $level);

		return $logger;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getContent()
	{
		return $this->logfile->getContent();
	}

	/**
	 * Test if a stream is working
	 */
	public function testStream()
	{
		$logfile = vfsStream::newFile('friendica.log')
			->at($this->root);

		$filehandler = fopen($logfile->url(), 'ab');

		$logger = new StreamLogger('test', $filehandler, $this->introspection, $this->fileSystem);
		$logger->emergency('working');

		$text = $logfile->getContent();

		self::assertLogline($text);
	}

	/**
	 * Test if the close statement is working
	 */
	public function testClose()
	{
		$logfile = vfsStream::newFile('friendica.log')
			->at($this->root);

		$logger = new StreamLogger('test', $logfile->url(), $this->introspection, $this->fileSystem);
		$logger->emergency('working');
		$logger->close();
		// close doesn't affect
		$logger->emergency('working too');

		$text = $logfile->getContent();

		self::assertLoglineNums(2, $text);
	}

	/**
	 * Test when a file isn't set
	 */
	public function testNoUrl()
	{
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage("Missing stream URL.");

		$logger = new StreamLogger('test', '', $this->introspection, $this->fileSystem);

		$logger->emergency('not working');
	}

	/**
	 * Test when a file cannot be opened
	 */
	public function testWrongUrl()
	{
		$this->expectException(\UnexpectedValueException::class);
		$this->expectExceptionMessageRegExp("/The stream or file .* could not be opened: .* /");

		$logfile = vfsStream::newFile('friendica.log')
			->at($this->root)->chmod(0);

		$logger = new StreamLogger('test', $logfile->url(), $this->introspection, $this->fileSystem);

		$logger->emergency('not working');
	}

	/**
	 * Test when the directory cannot get created
	 */
	public function testWrongDir()
	{
		$this->expectException(\UnexpectedValueException::class);
		$this->expectExceptionMessageRegExp("/Directory .* cannot get created: .* /");

		static::markTestIncomplete('We need a platform independent way to set directory to readonly');

		$logger = new StreamLogger('test', '/$%/wrong/directory/file.txt', $this->introspection, $this->fileSystem);

		$logger->emergency('not working');
	}

	/**
	 * Test when the minimum level is not valid
	 */
	public function testWrongMinimumLevel()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessageRegExp("/The level \".*\" is not valid./");

		$logger = new StreamLogger('test', 'file.text', $this->introspection, $this->fileSystem, 'NOPE');
	}

	/**
	 * Test when the minimum level is not valid
	 */
	public function testWrongLogLevel()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessageRegExp("/The level \".*\" is not valid./");

		$logfile = vfsStream::newFile('friendica.log')
			->at($this->root);

		$logger = new StreamLogger('test', $logfile->url(), $this->introspection, $this->fileSystem);

		$logger->log('NOPE', 'a test');
	}

	/**
	 * Test when the file is null
	 */
	public function testWrongFile()
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage("A stream must either be a resource or a string.");

		$logger = new StreamLogger('test', null, $this->introspection, $this->fileSystem);
	}

	/**
	 * Test a relative path
	 */
	public function testRealPath()
	{
		static::markTestSkipped('vfsStream isn\'t compatible with chdir, so not testable.');

		$logfile = vfsStream::newFile('friendica.log')
		                    ->at($this->root);

		chdir($this->root->getChild('logs')->url());

		$logger = new StreamLogger('test', '../friendica.log' , $this->introspection, $this->fileSystem);

		$logger->info('Test');
	}
}
