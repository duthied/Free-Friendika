<?php
/**
 * DatabaseTest class.
 */

namespace Friendica\Test;

use Friendica\App\Mode;
use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Database\Database;
use Friendica\Factory\ConfigFactory;
use Friendica\Factory\DBFactory;
use Friendica\Factory\ProfilerFactory;
use Friendica\Util\BasePath;
use Friendica\Util\ConfigFileLoader;
use Friendica\Util\Profiler;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit_Extensions_Database_DB_IDatabaseConnection;

require_once __DIR__ . '/../boot.php';

/**
 * Abstract class used by tests that need a database.
 */
abstract class DatabaseTest extends MockedTest
{
	use TestCaseTrait;

	/** @var Database */
	protected static $dba;

	/** @var BasePath */
	protected static $basePath;

	/** @var Mode */
	protected static $mode;

	/** @var ConfigCache */
	protected static $configCache;

	/** @var Profiler */
	protected static $profiler;

	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();

		self::$basePath = BasePath::create(dirname(__DIR__));
		self::$mode = new Mode(self::$basePath);
		$configLoader = new ConfigFileLoader(self::$basePath, self::$mode);
		self::$configCache = ConfigFactory::createCache($configLoader);
		self::$profiler = ProfilerFactory::create(self::$configCache);
		self::$dba = DBFactory::init(self::$configCache, self::$profiler, $_SERVER);
	}

	/**
	 * Get database connection.
	 *
	 * This function is executed before each test in order to get a database connection that can be used by tests.
	 * If no prior connection is available, it tries to create one using the USER, PASS and DB environment variables.
	 *
	 * If it could not connect to the database, the test is skipped.
	 *
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
	 * @see https://phpunit.de/manual/5.7/en/database.html
	 */
	protected function getConnection()
	{
		if (!getenv('MYSQL_DATABASE')) {
			$this->markTestSkipped('Please set the MYSQL_* environment variables to your test database credentials.');
		}

		if (!self::$dba->isConnected()) {
			if (!self::$dba->connect()) {
				$this->markTestSkipped('Could not connect to the database.');
			}
		}

		return $this->createDefaultDBConnection(self::$dba->getConnection(), getenv('MYSQL_DATABASE'));
	}

	/**
	 * Get dataset to populate the database with.
	 * @return YamlDataSet
	 * @see https://phpunit.de/manual/5.7/en/database.html
	 */
	protected function getDataSet()
	{
		return new YamlDataSet(__DIR__ . '/datasets/api.yml');
	}
}
