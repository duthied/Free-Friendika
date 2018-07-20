<?php

namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Lock\DatabaseLockDriver;
use Friendica\Database\dba;

class DatabaseLockDriverTest extends LockTest
{
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
