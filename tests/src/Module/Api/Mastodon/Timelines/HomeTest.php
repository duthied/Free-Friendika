<?php

namespace Friendica\Test\src\Module\Api\Mastodon\Timelines;

use Friendica\Test\src\Module\Api\ApiTest;

class HomeTest extends ApiTest
{
	/**
	 * Test the api_statuses_home_timeline() function.
	 *
	 * @return void
	 */
	public function testApiStatusesHomeTimeline()
	{
		/*
		$_REQUEST['max_id']          = 10;
		$_REQUEST['exclude_replies'] = true;
		$_REQUEST['conversation_id'] = 1;
		$result                      = api_statuses_home_timeline('json');
		self::assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_statuses_home_timeline() function with a negative page parameter.
	 *
	 * @return void
	 */
	public function testApiStatusesHomeTimelineWithNegativePage()
	{
		/*
		$_REQUEST['page'] = -2;
		$result           = api_statuses_home_timeline('json');
		self::assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_statuses_home_timeline() with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiStatusesHomeTimelineWithUnallowedUser()
	{
		/*
		$this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		BasicAuth::setCurrentUserID();
		api_statuses_home_timeline('json');
		*/
	}

	/**
	 * Test the api_statuses_home_timeline() function with an RSS result.
	 *
	 * @return void
	 */
	public function testApiStatusesHomeTimelineWithRss()
	{
		// $result = api_statuses_home_timeline('rss');
		// self::assertXml($result, 'statuses');
	}
}
