<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Test\Protocol;

use Friendica\Protocol\Activity;
use Friendica\Protocol\ActivityNamespace;
use Friendica\Test\MockedTest;

class ActivityTest extends MockedTest
{
	public function dataMatch()
	{
		return [
			'empty' => [
				'haystack' => '',
				'needle' => '',
				'assert' => true,
			],
			'simple' => [
				'haystack' => Activity\ObjectType::TAGTERM,
				'needle' => Activity\ObjectType::TAGTERM,
				'assert' => true,
			],
			'withNamespace' => [
				'haystack' => 'tagterm',
				'needle' => ActivityNamespace::ACTIVITY_SCHEMA . Activity\ObjectType::TAGTERM,
				'assert' => true,
			],
			'invalidSimple' => [
				'haystack' => 'tagterm',
				'needle' => '',
				'assert' => false,
			],
			'invalidWithOutNamespace' => [
				'haystack' => 'tagterm',
				'needle' => Activity\ObjectType::TAGTERM,
				'assert' => false,
			],
			'withSubPath' => [
				'haystack' => 'tagterm',
				'needle' => ActivityNamespace::ACTIVITY_SCHEMA . '/bla/' . Activity\ObjectType::TAGTERM,
				'assert' => true,
			],
		];
	}

	/**
	 * Test the different, possible matchings
	 *
	 * @dataProvider dataMatch
	 */
	public function testMatch(string $haystack, string $needle, bool $assert)
	{
		$activity = new Activity();

		$this->assertEquals($assert, $activity->match($haystack, $needle));
	}

	public function testIsHidden()
	{
		$activity = new Activity();

		$this->assertTrue($activity->isHidden(Activity::LIKE));
		$this->assertFalse($activity->isHidden(Activity\ObjectType::BOOKMARK));
	}
}
