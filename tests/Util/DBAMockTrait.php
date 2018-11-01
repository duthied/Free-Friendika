<?php

namespace Friendica\Test\Util;

use Mockery\MockInterface;

/**
 * Trait to mock the DBA connection status
 */
trait DBAMockTrait
{
	/**
	 * @var MockInterface The mocking interface of Friendica\Database\DBA
	 */
	private $dbaMock;

	/**
	 * Mocking DBA::connect()
	 *
	 * @param bool $return True, if the connect was successful, otherwise false
	 * @param null|int $times How often the method will get used
	 */
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

	/**
	 * Mocking DBA::connected()
	 *
	 * @param bool $return True, if the DB is connected, otherwise false
	 * @param null|int $times How often the method will get used
	 */
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

	/**
	 * Mocking DBA::fetchFirst()
	 *
	 * @param string $arg The argument of fetchFirst
	 * @param bool $return True, if the DB is connected, otherwise false
	 * @param null|int $times How often the method will get used
	 */
	public function mockFetchFirst($arg, $return = true, $times = null)
	{
		if (!isset($this->dbaMock)) {
			$this->dbaMock = \Mockery::mock('alias:Friendica\Database\DBA');
		}

		$this->dbaMock
			->shouldReceive('fetchFirst')
			->with($arg)
			->times($times)
			->andReturn($return);
	}
}
