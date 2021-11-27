<?php

namespace Friendica\Test\src\Module\Api\Twitter\Statuses;

use Friendica\Test\src\Module\Api\ApiTest;

class UserTimelineTest extends ApiTest
{
	/**
	 * Test the api_statuses_user_timeline() function.
	 *
	 * @return void
	 */
	public function testApiStatusesUserTimeline()
	{
		/*
			$_REQUEST['user_id']         = 42;
			$_REQUEST['max_id']          = 10;
			$_REQUEST['exclude_replies'] = true;
			$_REQUEST['conversation_id'] = 7;

			$result = api_statuses_user_timeline('json');
			self::assertNotEmpty($result['status']);
			foreach ($result['status'] as $status) {
				self::assertStatus($status);
			}
			*/
	}

	/**
	 * Test the api_statuses_user_timeline() function with a negative page parameter.
	 *
	 * @return void
	 */
	public function testApiStatusesUserTimelineWithNegativePage()
	{
		/*
		$_REQUEST['user_id'] = 42;
		$_REQUEST['page']    = -2;

		$result = api_statuses_user_timeline('json');
		self::assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_statuses_user_timeline() function with an RSS result.
	 *
	 * @return void
	 */
	public function testApiStatusesUserTimelineWithRss()
	{
		// $result = api_statuses_user_timeline('rss');
		// self::assertXml($result, 'statuses');
	}

	/**
	 * Test the api_statuses_user_timeline() function with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiStatusesUserTimelineWithUnallowedUser()
	{
		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_statuses_user_timeline('json');
	}
}
