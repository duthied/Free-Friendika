<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test;

use Friendica\Database\Database;
use Friendica\Test\Util\Database\StaticDatabase;

/**
 * Abstract class used by tests that need a database.
 */
trait DatabaseTestTrait
{
	protected function setUpDb()
	{
		StaticDatabase::statConnect($_SERVER);
		// Rollbacks every DB usage (in case the test couldn't call tearDown)
		StaticDatabase::statRollback();
		// Start the first, outer transaction
		StaticDatabase::getGlobConnection()->beginTransaction();
	}

	protected function tearDownDb()
	{
		try {
			// Rollbacks every DB usage so we don't commit anything into the DB
			StaticDatabase::statRollback();
		} catch (\PDOException $exception) {
			print_r("Found already rolled back transaction");
		}
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
			if (is_numeric($tableName)) {
				continue;
			}

			if (!is_array($rows)) {
				$dba->p('TRUNCATE TABLE `' . $tableName . '``');
				continue;
			}

			foreach ($rows as $row) {
				$dba->insert($tableName, $row, true);
			}
		}
	}
}
