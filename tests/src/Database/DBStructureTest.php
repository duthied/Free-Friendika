<?php

namespace Friendica\Test\src\Database;

use Friendica\App;
use Friendica\Core\Config\Cache\PConfigCache;
use Friendica\Core\L10n\L10n;
use Friendica\Database\DBStructure;
use Friendica\Factory;
use Friendica\Model\Config\Config;
use Friendica\Test\DatabaseTest;
use Friendica\Util\BaseURL;

class DBStructureTest extends DatabaseTest
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function setUp()
	{
		$configModel = new Config(self::$dba);
		$configFactory = new Factory\ConfigFactory();
		$config = $configFactory->createConfig(self::$configCache, $configModel);
		$pconfigModel = new \Friendica\Model\Config\PConfig(self::$dba);
		$configFactory->createPConfig(self::$configCache, new PConfigCache(), $pconfigModel);
		$loggerFactory = new Factory\LoggerFactory();
		$logger = $loggerFactory->create('test', self::$dba, $config, self::$profiler);
		$baseUrl = new BaseURL($config, $_SERVER);
		$router = new App\Router();
		$l10n = new L10n($config,
			self::$dba,
			$logger);
		$this->app = new App(self::$dba, $config, self::$mode, $router, $baseUrl, $logger, self::$profiler, $l10n, false);
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
