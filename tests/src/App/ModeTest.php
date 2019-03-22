<?php

namespace Friendica\Test\src\App;

use Friendica\App\Mode;
use Friendica\Core\Config;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\DBAMockTrait;
use Friendica\Test\Util\VFSTrait;

class ModeTest extends MockedTest
{
	use VFSTrait;
	use DBAMockTrait;

	public function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();
	}

	public function testItEmpty()
	{
		$mode = new Mode($this->root->url());
		$this->assertTrue($mode->isInstall());
		$this->assertFalse($mode->isNormal());
	}

	public function testWithoutConfig()
	{
		$mode = new Mode($this->root->url());

		$this->assertTrue($this->root->hasChild('config/local.config.php'));

		$this->delConfigFile('local.config.php');

		$this->assertFalse($this->root->hasChild('config/local.config.php'));

		$mode->determine();

		$this->assertTrue($mode->isInstall());
		$this->assertFalse($mode->isNormal());

		$this->assertFalse($mode->has(Mode::LOCALCONFIGPRESENT));
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testWithoutDatabase()
	{
		$this->mockConnected(false, 1);

		$mode = new Mode($this->root->url());
		$mode->determine();

		$this->assertFalse($mode->isNormal());
		$this->assertTrue($mode->isInstall());

		$this->assertTrue($mode->has(Mode::LOCALCONFIGPRESENT));
		$this->assertFalse($mode->has(Mode::DBAVAILABLE));
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testWithoutDatabaseSetup()
	{
		$this->mockConnected(true, 1);
		$this->mockFetchFirst('SHOW TABLES LIKE \'config\'', false, 1);

		$mode = new Mode($this->root->url());
		$mode->determine();

		$this->assertFalse($mode->isNormal());
		$this->assertTrue($mode->isInstall());

		$this->assertTrue($mode->has(Mode::LOCALCONFIGPRESENT));
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testWithMaintenanceMode()
	{
		$this->mockConnected(true, 1);
		$this->mockFetchFirst('SHOW TABLES LIKE \'config\'', true, 1);

		$config = \Mockery::mock(Config\Configuration::class);
		$config
			->shouldReceive('get')
			->with('system', 'maintenance', null, false)
			->andReturn(true)
			->once();
		// Initialize empty Config
		Config::init($config);

		$mode = new Mode($this->root->url());
		$mode->determine();

		$this->assertFalse($mode->isNormal());
		$this->assertFalse($mode->isInstall());

		$this->assertTrue($mode->has(Mode::DBCONFIGAVAILABLE));
		$this->assertFalse($mode->has(Mode::MAINTENANCEDISABLED));
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testNormalMode()
	{
		$this->mockConnected(true, 1);
		$this->mockFetchFirst('SHOW TABLES LIKE \'config\'', true, 1);

		$config = \Mockery::mock(Config\Configuration::class);
		$config
			->shouldReceive('get')
			->with('system', 'maintenance', null, false)
			->andReturn(false)
			->once();
		// Initialize empty Config
		Config::init($config);

		$mode = new Mode($this->root->url());
		$mode->determine();

		$this->assertTrue($mode->isNormal());
		$this->assertFalse($mode->isInstall());

		$this->assertTrue($mode->has(Mode::DBCONFIGAVAILABLE));
		$this->assertTrue($mode->has(Mode::MAINTENANCEDISABLED));
	}
}
