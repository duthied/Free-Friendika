<?php

namespace Friendica\Test\Util;

/**
 * Trait to mock the DBA connection status
 */
trait DBAMockTrait
{
	private $dbaMock;

	public function mockConnect($return = true, $times = null)
	{
		if (!isset($this->dbaMock)) {
			$this->dbaMock = \Mockery::mock('alias:Friendica\Database\DBA');
		}

		$this->dbaMock
			->shouldReceive('connect')
			->times($times)
			->andReturn($return);
	}

	public function mockConnected($return = true, $times = null)
	{
		if (!isset($this->dbaMock)) {
			$this->dbaMock = \Mockery::mock('alias:Friendica\Database\DBA');
		}

		$this->dbaMock
			->shouldReceive('connected')
			->times($times)
			->andReturn($return);
	}
}
