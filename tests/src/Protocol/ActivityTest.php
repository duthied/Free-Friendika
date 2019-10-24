<?php

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
