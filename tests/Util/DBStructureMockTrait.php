<?php

namespace Friendica\Test\Util;

/**
 * Trait to mock the DBStructure connection status
 */
trait DBStructureMockTrait
{
	private $dbStructure;

	public function mockUpdate($args = [], $return = true, $times = null)
	{
		if (!isset($this->dbStructure)) {
			$this->dbStructure = \Mockery::mock('alias:Friendica\Database\DBStructure');
		}

		$this->dbStructure
			->shouldReceive('update')
			->withArgs($args)
			->times($times)
			->andReturn($return);
	}

	public function mockExistsTable($tableName, $return = true, $times = null)
	{
		if (!isset($this->dbStructure)) {
			$this->dbStructure = \Mockery::mock('alias:Friendica\Database\DBStructure');
		}

		$this->dbStructure
			->shouldReceive('existsTable')
			->with($tableName)
			->times($times)
			->andReturn($return);
	}
}
