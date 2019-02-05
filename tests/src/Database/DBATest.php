<?php
namespace Friendica\Test\Database;

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Database\DBA;
use Friendica\Factory;
use Friendica\Test\DatabaseTest;
use Friendica\Util\BasePath;

class DBATest extends DatabaseTest
{
	public function setUp()
	{
		$basedir = BasePath::create(dirname(__DIR__) . '/../../');
		$configLoader = new Config\ConfigCacheLoader($basedir);
		$config = Factory\ConfigFactory::createCache($configLoader);
		$logger = Factory\LoggerFactory::create('test', $config);
		$this->app = new App($config, $logger, false);
		$this->logOutput = FActory\LoggerFactory::enableTest($this->app->getLogger());

		parent::setUp();

		// Default config
		Config::set('config', 'hostname', 'localhost');
		Config::set('system', 'throttle_limit_day', 100);
		Config::set('system', 'throttle_limit_week', 100);
		Config::set('system', 'throttle_limit_month', 100);
		Config::set('system', 'theme', 'system_theme');
	}

	/**
	 * @small
	 */
	public function testExists() {

		$this->assertTrue(DBA::exists('config', []));
		$this->assertFalse(DBA::exists('notable', []));

		$this->assertTrue(DBA::exists('config', null));
		$this->assertFalse(DBA::exists('notable', null));

		$this->assertTrue(DBA::exists('config', ['k' => 'hostname']));
		$this->assertFalse(DBA::exists('config', ['k' => 'nonsense']));
	}
}
