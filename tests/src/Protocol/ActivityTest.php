<?php

namespace Friendica\Test\Protocol;

use Friendica\Protocol\Activity;
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
				'haystack' => ACTIVITY_OBJ_TAGTERM,
				'needle' => ACTIVITY_OBJ_TAGTERM,
				'assert' => true,
			],
			'withNamespace' => [
				'haystack' => 'tagterm',
				'needle' => NAMESPACE_ACTIVITY_SCHEMA . ACTIVITY_OBJ_TAGTERM,
				'assert' => true,
			],
			'invalidSimple' => [
				'haystack' => 'tagterm',
				'needle' => '',
				'assert' => false,
			],
			'invalidWithOutNamespace' => [
				'haystack' => 'tagterm',
				'needle' => ACTIVITY_OBJ_TAGTERM,
				'assert' => false,
			],
			'withSubPath' => [
				'haystack' => 'tagterm',
				'needle' =>  NAMESPACE_ACTIVITY_SCHEMA . '/bla/' . ACTIVITY_OBJ_TAGTERM,
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
}
