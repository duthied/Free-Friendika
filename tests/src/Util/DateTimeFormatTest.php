<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

		self::assertEquals($assert, $dtFormat->isYearMonth($input));
	}

	/**
	 * Test the DateTimeFormat::API output.
	 *
	 * @return void
	 */
	public function testApiDate()
	{
		self::assertEquals('Wed Oct 10 00:00:00 +0000 1990', DateTimeFormat::utc('1990-10-10', DateTimeFormat::API));
	}

	public function dataFix(): array
	{
		return [
			'Mo, 19 Sep 2022 14:51:00 +0200' => [
				'expected' => '19 Sep 2022 14:51:00 +0200',
				'dateString' => 'Mo, 19 Sep 2022 14:51:00 +0200',
			],
			'2020-11-21T12:00:13.745339ZZ' => [
				'expected' => '2020-11-21T12:00:13.745339Z',
				'dateString' => '2020-11-21T12:00:13.745339ZZ',
			],
			'2016-09-09T13:32:00ZZ' => [
				'expected' => '2016-09-09T13:32:00Z',
				'dateString' => '2016-09-09T13:32:00ZZ',
			],
			'2021-09-09T16:19:00ZZ' => [
				'expected' => '2021-09-09T16:19:00Z',
				'dateString' => '2021-09-09T16:19:00ZZ',
			],
			'Sun, 10/03/2021 - 12:41' => [
				'expected' => 'Sun, 10/03/2021 12:41',
				'dateString' => 'Sun, 10/03/2021 - 12:41',
			],
			'Mon, 09/12/2022 - 09:02' => [
				'expected' => 'Mon, 09/12/2022 09:02',
				'dateString' => 'Mon, 09/12/2022 - 09:02',
			],
			'4:30 PM, Sep 13, 2022' => [
				'expected' => '4:30 PM Sep 13 2022',
				'dateString' => '4:30 PM, Sep 13, 2022',
			],
			'August 27, 2022 - 21:00' => [
				'expected' => 'August 27, 2022, 21:00',
				'dateString' => 'August 27, 2022 - 21:00',
			],
			'2021-09-19T14:06:03&#x2B;00:00' => [
				'expected' => '2021-09-19T14:06:03+00:00',
				'dateString' => '2021-09-19T14:06:03&#x2B;00:00',
			],
		];
	}

	/**
	 * @dataProvider dataFix
	 *
	 * @param $expected
	 * @param $dateString
	 * @return void
	 */
	public function testFix($expected, $dateString)
	{
		$this->assertEquals($expected, DateTimeFormat::fix($dateString));
	}
}
