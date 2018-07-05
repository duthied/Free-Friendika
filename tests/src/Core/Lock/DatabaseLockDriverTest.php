<?php

namespace Friendica\Test\src\Core\Lock;

use dba;
use Friendica\Core\Lock\DatabaseLockDriver;
use Friendica\Database\DBStructure;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit_Extensions_Database_DB_IDatabaseConnection;

class DatabaseLockDriverTest extends LockTest
{
	use TestCaseTrait;

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
		if (!dba::$connected) {
			dba::connect('localhost', getenv('USER'), getenv('PASS'), getenv('DB'));

			if (dba::$connected) {
				$app = get_app();
				// We need to do this in order to disable logging
				$app->module = 'install';

				// Create database structure
				DBStructure::update(false, true, true);
			} else {
				$this->markTestSkipped('Could not connect to the database.');
			}
		}

		return $this->createDefaultDBConnection(dba::get_db(), getenv('DB'));
	}

	/**
	 * Get dataset to populate the database with.
	 * @return YamlDataSet
	 * @see https://phpunit.de/manual/5.7/en/database.html
	 */
	protected function getDataSet()
	{
		return new YamlDataSet(__DIR__ . '/../../../datasets/api.yml');
	}

	protected function getInstance()
	{
		return new DatabaseLockDriver();
	}

	public function tearDown()
	{
		dba::delete('locks', [ 'id > 0']);
		parent::tearDown();
	}
}