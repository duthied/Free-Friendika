<?php

namespace Friendica\Test\src\Util;

use Friendica\Test\MockedTest;
use Friendica\Util\DateTimeFormat;

class DateTimeFormatTest extends MockedTest
{
	public function dataYearMonth()
	{
		return [
			'validNormal' => [
				'input' => '1990-10',
				'assert' => true,
			],
			'validOneCharMonth' => [
				'input' => '1990-1',
				'assert' => true,
			],
			'validTwoCharMonth' => [
				'input' => '1990-01',
				'assert' => true,
			],
			'invalidFormat' => [
				'input' => '199-11',
				'assert' => false,
			],
			'invalidFormat2' => [
				'input' => '1990-15',
				'assert' => false,
			],
			'invalidFormat3' => [
				'input' => '99-101',
				'assert' => false,
			],
			'invalidFormat4' => [
				'input' => '11-1990',
				'assert' => false,
			],
			'invalidFuture' => [
				'input' => '3030-12',
				'assert' => false,
			],
			'invalidYear' => [
				'input' => '-100-10',
				'assert' => false,
			],
		];
	}

	/**
	 * @dataProvider dataYearMonth
	 */
	public function testIsYearMonth(string $input, bool $assert)
	{
		$dtFormat = new DateTimeFormat();

		$this->assertEquals($assert, $dtFormat->isYearMonth($input));
	}
}
