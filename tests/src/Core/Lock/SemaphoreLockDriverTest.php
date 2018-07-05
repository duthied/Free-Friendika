<?php

namespace Friendica\Test\Core\Lock;


use Friendica\Core\Lock\SemaphoreLockDriver;

class SemaphoreLockDriverTest extends LockTest
{
	protected function getInstance()
	{
		return new SemaphoreLockDriver();
	}
}