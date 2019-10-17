<?php
/**
 * DatabaseTest class.
 */

namespace Friendica\Test;

use Friendica\Database\Database;
use Friendica\Test\Util\Database\StaticDatabase;

/**
 * Abstract class used by tests that need a database.
 */
trait DatabaseTestTrait
{
	protected function setUp()
	{
		StaticDatabase::statConnect($_SERVER);
		// Rollbacks every DB usage (in case the test couldn't call tearDown)
		StaticDatabase::statRollback();
		// Start the first, outer transaction
		StaticDatabase::getGlobConnection()->beginTransaction();

		parent::setUp();
	}

	protected function tearDown()
	{
		// Rollbacks every DB usage so we don't commit anything into the DB
		StaticDatabase::statRollback();

		parent::tearDown();
	}

	/**
	 * Loads a given DB fixture for this DB test
	 *
	 * @param string   $fixture The path to the fixture
	 * @param Database $dba     The DB connection
	 *
	 * @throws \Exception
	 */
	protected function loadFixture(string $fixture, Database $dba)
	{
		$data = include $fixture;

		foreach ($data as $tableName => $rows) {
			if (!is_array($rows)) {
				$dba->p('TRUNCATE TABLE `' . $tableName . '``');
				continue;
			}

			foreach ($rows as $row) {
				$dba->insert($tableName, $row);
			}
		}
	}
}
