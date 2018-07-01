<?php
/**
 * DatabaseTest class.
 */

namespace Friendica\Test;

use dba;
use Friendica\Database\DBStructure;
use PHPUnit_Extensions_Database_DB_IDatabaseConnection;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\Framework\TestCase;

/**
 * Abstract class used by tests that need a database.
 */
abstract class DatabaseTest extends TestCase
{

	use TestCaseTrait;

	/**
	 * Renames an eventually existing .htconfig.php to .htconfig.php.tmp
	 * Creates a new .htconfig.php for bin/worker.php execution
	 */
	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();

		$base_config_file_name = 'htconfig.php';
		$config_file_name = '.htconfig.php';

		$base_config_file_path = stream_resolve_include_path($base_config_file_name);
		$config_file_path = dirname($base_config_file_path) . DIRECTORY_SEPARATOR . $config_file_name;
		$config_file_path_tmp = $config_file_path . '.tmp';

		if (file_exists($config_file_path)) {
			rename($config_file_path, $config_file_path_tmp);
		}

		$config_string = file_get_contents($base_config_file_path);

		$config_string = str_replace('die(', '// die(', $config_string);

		file_put_contents($config_file_path, $config_string);
	}

	/**
	 * Delete the created .htconfig.php
	 * Renames an eventually existing .htconfig.php.tmp to .htconfig.php
	 */
	public static function tearDownAfterClass()
	{
		$base_config_file_name = 'htconfig.php';
		$config_file_name = '.htconfig.php';

		$base_config_file_path = stream_resolve_include_path($base_config_file_name);
		$config_file_path = dirname($base_config_file_path) . DIRECTORY_SEPARATOR . $config_file_name;
		$config_file_path_tmp = $config_file_path . '.tmp';

		if (file_exists($config_file_path)) {
			unlink($config_file_path);
		}

		if (file_exists($config_file_path_tmp)) {
			rename($config_file_path_tmp, $config_file_path);
		}
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
		if (!dba::connected()) {
			$this->markTestSkipped('Could not connect to the database.');
		}

		return $this->createDefaultDBConnection(dba::get_db(), getenv('MYSQL_DATABASE'));
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
