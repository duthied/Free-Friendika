<?php

namespace Friendica\Test\src\Util\Logger;

use Friendica\Test\MockedTest;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\Introspection;
use Friendica\Util\Logger\StreamLogger;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use Psr\Log\LogLevel;

class StreamLoggerTest extends MockedTest
{
	const LOGLINE = '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} .* \[.*\]: .* \{.*\"file\":\".*\".*,.*\"line\":\d*,.*\"function\":\".*\".*,.*\"uid\":\".*\".*,.*\"process_id\":\d*.*\}/';

	const FILE = 'test';
	const LINE = 666;
	const FUNC = 'myfunction';

	use VFSTrait;

	/**
	 * @var Introspection|MockInterface
	 */
	private $introspection;

	protected function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();

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

	public function testNormal()
	{
		$logfile = vfsStream::newFile('friendica.log')
			->at($this->root);

		$logger = new StreamLogger('test', $logfile->url(), $this->introspection);
		$logger->emergency('working!');
		$logger->alert('working too!');
		$logger->debug('and now?');
		$logger->notice('message', ['an' => 'context']);

		$text = $logfile->getContent();
		$this->assertLogline($text);
		$this->assertLoglineNums(4, $text);
	}

	/**
	 * Test if a log entry is correctly interpolated
	 */
	public function testPsrInterpolate()
	{
		$logfile = vfsStream::newFile('friendica.log')
			->at($this->root);

		$logger = new StreamLogger('test', $logfile->url(), $this->introspection);

		$logger->emergency('A {psr} test', ['psr' => 'working']);
		$logger->alert('An {array} test', ['array' => ['it', 'is', 'working']]);
		$text = $logfile->getContent();
		$this->assertContains('A working test', $text);
		$this->assertContains('An ["it","is","working"] test', $text);
	}

	/**
	 * Test if a log entry contains all necessary information
	 */
	public function testContainsInformation()
	{
		$logfile = vfsStream::newFile('friendica.log')
			->at($this->root);

		$logger = new StreamLogger('test', $logfile->url(), $this->introspection);

		$logger->emergency('A test');

		$text = $logfile->getContent();
		$this->assertContains('"process_id":' . getmypid(), $text);
		$this->assertContains('"file":"' . self::FILE . '"', $text);
		$this->assertContains('"line":' . self::LINE, $text);
		$this->assertContains('"function":"' . self::FUNC . '"', $text);
	}

	/**
	 * Test if the minimum level is working
	 */
	public function testMinimumLevel()
	{
		$logfile = vfsStream::newFile('friendica.log')
			->at($this->root);

		$logger = new StreamLogger('test', $logfile->url(), $this->introspection, LogLevel::NOTICE);

		$logger->emergency('working');
		$logger->alert('working');
		$logger->error('working');
		$logger->warning('working');
		$logger->notice('working');
		$logger->info('not working');
		$logger->debug('not working');

		$text = $logfile->getContent();

		$this->assertLoglineNums(5, $text);
	}


	/**
	 * Test if a file cannot get opened
	 * @expectedException \UnexpectedValueException
	 */
	public function testNoFile()
	{
		$logfile = vfsStream::newFile('friendica.log')
			->at($this->root)
			->chmod(0);

		$logger = new StreamLogger('test', $logfile->url(), $this->introspection);

		$logger->emergency('not working');
	}

	/**
	 * Test when a file isn't set
	 * @expectedException \LogicException
	 * @expectedExceptionMessage Missing stream URL.
	 */
	public function testNoUrl()
	{
		$logger = new StreamLogger('test', '', $this->introspection);

		$logger->emergency('not working');
	}

	/**
	 * Test when a file doesn't exist
	 * @expectedException \UnexpectedValueException
	 * @expectedExceptionMessageRegExp /The stream or file .* could not be opened: .* /
	 */
	public function testWrongUrl()
	{
		$logger = new StreamLogger('test', 'wrongfile', $this->introspection);

		$logger->emergency('not working');
	}

	/**
	 * Test when the directory cannot get created
	 * @expectedException \UnexpectedValueException
	 * @expectedExceptionMessageRegExp /Directory .* cannot get created: .* /
	 */
	public function testWrongDir()
	{
		$logger = new StreamLogger('test', 'a/wrong/directory/file.txt', $this->introspection);

		$logger->emergency('not working');
	}

	/**
	 * Test when the minimum level is not valid
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessageRegExp /The level ".*" is not valid./
	 */
	public function testWrongMinimumLevel()
	{
		$logger = new StreamLogger('test', 'file.text', $this->introspection, 'NOPE');
	}

	/**
	 * Test when the minimum level is not valid
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessageRegExp /The level ".*" is not valid./
	 */
	public function testWrongLogLevel()
	{
		$logfile = vfsStream::newFile('friendica.log')
			->at($this->root);

		$logger = new StreamLogger('test', $logfile->url(), $this->introspection);

		$logger->log('NOPE', 'a test');
	}
}
