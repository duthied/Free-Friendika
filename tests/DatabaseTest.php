<?php
/**
 * DatabaseTest class.
 */

namespace Friendica\Test;

/**
 * Abstract class used by tests that need a database.
 */
abstract class DatabaseTest extends MockedTest
{
	use DatabaseTestTrait;
}
