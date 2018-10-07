<?php

namespace Friendica\Test\src\Core\Console;

use Friendica\App;
use Friendica\BaseObject;
use Friendica\Database\DBA;
use Friendica\Test\Util\Intercept;
use Friendica\Test\Util\VFSTrait;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

abstract class ConsoleTest extends TestCase
{
	use VFSTrait;

	/**
	 * @var MultiUseConsole Extension of the basic Friendica Console for testing purpose
	 */
	private $console;
	/**
	 * @var App The Friendica App
	 */
	protected $app;

	protected $stdout;

	protected function setUp()
	{
		parent::setUp();

		Intercept::setUp();

		if (!getenv('MYSQL_DATABASE')) {
			$this->markTestSkipped('Please set the MYSQL_* environment variables to your test database credentials.');
		}

		$this->setUpVfsDir();

		// fake console.php for setting an executable
		vfsStream::newFile('console.php')
			->at($this->root->getChild('bin'))
			->setContent('<? php');

		// Reusable App object
		$this->app = new App($this->root->url());
		BaseObject::setApp($this->app);
		$this->console = new MultiUseConsole();
	}

	public function execute($args) {
		DBA::disconnect();
		$this->app->reload();

		array_unshift($args, $this->getExecutablePath());
		Intercept::reset();
		$this->console->reset();
		$this->console->parseTestArgv($args);
		$this->console->execute();

		$returnStr = Intercept::$cache;
		Intercept::reset();
		return $returnStr;
	}

	/**
	 * @return string returns the path to the console executable during tests
	 */
	protected function getExecutablePath() {
		return $this->root->getChild('bin' . DIRECTORY_SEPARATOR . 'console.php')->url();
	}
}
