<?php
/**
 * DatabaseTest class.
 */

namespace Friendica\Test;

use Friendica\Test\Util\Database\StaticDatabase;

/**
 * Abstract class used by tests that need a database.
 */
abstract class DatabaseTest extends MockedTest
{
	protected function setUp()
	{
		parent::setUp();

		StaticDatabase::statConnect($_SERVER);
		// Rollbacks every DB usage (in case the test couldn't call tearDown)
		StaticDatabase::statRollback();
		// Start the first, outer transaction
		StaticDatabase::getGlobConnection()->beginTransaction();
	}

	protected function tearDown()
	{
		// Rollbacks every DB usage so we don't commit anything into the DB
		StaticDatabase::statRollback();

		parent::tearDown();
	}
}
