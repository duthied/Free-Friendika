<?php

namespace Friendica\Test\src\Database;

use Friendica\App;
use Friendica\Core\Config\Cache\PConfigCache;
use Friendica\Database\DBStructure;
use Friendica\Factory;
use Friendica\Test\DatabaseTest;
use Friendica\Util\BaseURL;

class DBStructureTest extends DatabaseTest
{
	public function setUp()
	{
		$configModel = new \Friendica\Model\Config\Config(self::$dba);
		$config = Factory\ConfigFactory::createConfig(self::$configCache, $configModel);
		Factory\ConfigFactory::createPConfig(new PConfigCache());
		$logger = Factory\LoggerFactory::create('test', self::$dba, $config, self::$profiler);
		$baseUrl = new BaseURL($config, $_SERVER);
		$router = new App\Router();
		$this->app = new App(self::$dba, $config, self::$mode, $router, $baseUrl, $logger, self::$profiler, false);

		parent::setUp();
	}

	/**
	 * @small
	 */
	public function testExists() {
		$this->assertTrue(DBStructure::existsTable('config'));

		$this->assertFalse(DBStructure::existsTable('notatable'));

		$this->assertTrue(DBStructure::existsColumn('config', ['k']));
		$this->assertFalse(DBStructure::existsColumn('config', ['nonsense']));
		$this->assertFalse(DBStructure::existsColumn('config', ['k', 'nonsense']));
	}

	/**
	 * @small
	 */
	public function testRename() {
		$fromColumn = 'k';
		$toColumn = 'key';
		$fromType = 'varbinary(255) not null';
		$toType = 'varbinary(255) not null comment \'Test To Type\'';

		$this->assertTrue(DBStructure::rename('config', [ $fromColumn => [ $toColumn, $toType ]]));
		$this->assertTrue(DBStructure::existsColumn('config', [ $toColumn ]));
		$this->assertFalse(DBStructure::existsColumn('config', [ $fromColumn ]));

		$this->assertTrue(DBStructure::rename('config', [ $toColumn => [ $fromColumn, $fromType ]]));
		$this->assertTrue(DBStructure::existsColumn('config', [ $fromColumn ]));
		$this->assertFalse(DBStructure::existsColumn('config', [ $toColumn ]));
	}

	/**
	 * @small
	 */
	public function testChangePrimaryKey() {
		$oldID = 'client_id';
		$newID = 'pw';

		$this->assertTrue(DBStructure::rename('clients', [ $newID ], DBStructure::RENAME_PRIMARY_KEY));
		$this->assertTrue(DBStructure::rename('clients', [ $oldID ], DBStructure::RENAME_PRIMARY_KEY));
	}
}
