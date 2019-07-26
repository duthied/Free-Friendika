<?php
/**
 * DatabaseTest class.
 */

namespace Friendica\Test;

use PDO;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit_Extensions_Database_DB_IDatabaseConnection;

/**
 * Abstract class used by tests that need a database.
 */
abstract class DatabaseTest extends MockedTest
{
	use TestCaseTrait;

	// only instantiate pdo once for test clean-up/fixture load
	static private $pdo = null;

	// only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
	private $conn = null;

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
		$server = $_SERVER;

		if ($this->conn === null) {
			if (self::$pdo == null) {

				if (!empty($server['MYSQL_HOST'])
				    && !empty($server['MYSQL_USERNAME'] || !empty($server['MYSQL_USER']))
				    && $server['MYSQL_PASSWORD'] !== false
				    && !empty($server['MYSQL_DATABASE'])) {

					$connect = "mysql:host=" . $server['MYSQL_HOST'] . ";dbname=" . $server['MYSQL_DATABASE'];

					if (!empty($server['MYSQL_PORT'])) {
						$connect .= ";port=" . $server['MYSQL_PORT'];
					}

					if (!empty($server['MYSQL_USERNAME'])) {
						$db_user = $server['MYSQL_USERNAME'];
					} else {
						$db_user = $server['MYSQL_USER'];
					}

					$db_pass = (string)$server['MYSQL_PASSWORD'];

					self::$pdo = @new PDO($connect, $db_user, $db_pass);
					self::$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
				}
			}
			$this->conn = $this->createDefaultDBConnection(self::$pdo, getenv('MYSQL_DATABASE'));
		}

		return $this->conn;
	}

	/**
	 * Get dataset to populate the database with.
	 *
	 * @return YamlDataSet
	 * @see https://phtablepunit.de/manual/5.7/en/database.html
	 */
	protected function getDataSet()
	{
		return new YamlDataSet(__DIR__ . '/datasets/api.yml');
	}
}
