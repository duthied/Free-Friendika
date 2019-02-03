<?php

namespace Friendica\Test\Util;

use Mockery\MockInterface;

trait DateTimeFormatMockTrait
{
	/**
	 * @var MockInterface The mocking interface of Friendica\Database\DBA
	 */
	private $dtfMock;

	public function mockUtcNow($time, $times = null)
	{
		if (!isset($this->dtfMock)) {
			$this->dtfMock = \Mockery::mock('alias:Friendica\Util\DateTimeFormat');
		}

		$this->dtfMock
			->shouldReceive('utcNow')
			->andReturn($time)
			->times($times);
	}

	public function mockUtc($input, $time, $times = null)
	{
		if (!isset($this->dtfMock)) {
			$this->dtfMock = \Mockery::mock('alias:Friendica\Util\DateTimeFormat');
		}

		$this->dtfMock
			->shouldReceive('utc')
			->with($input)
			->andReturn($time)
			->times($times);
	}
}
