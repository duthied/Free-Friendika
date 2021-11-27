<?php

namespace Friendica\Test\src\Module\Api\Mastodon\Timelines;

use Friendica\Test\src\Module\Api\ApiTest;

class PublicTimelineTest extends ApiTest
{
	/**
	 * Test the api_statuses_public_timeline() function.
	 *
	 * @return void
	 */
	public function testApiStatusesPublicTimeline()
	{
		/*
		$_REQUEST['max_id']          = 10;
		$_REQUEST['conversation_id'] = 1;
		$result                      = api_statuses_public_timeline('json');
		self::assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_statuses_public_timeline() function with the exclude_replies parameter.
	 *
	 * @return void
	 */
	public function testApiStatusesPublicTimelineWithExcludeReplies()
	{
		/*
		$_REQUEST['max_id']          = 10;
		$_REQUEST['exclude_replies'] = true;
		$result                      = api_statuses_public_timeline('json');
		self::assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_statuses_public_timeline() function with a negative page parameter.
	 *
	 * @return void
	 */
	public function testApiStatusesPublicTimelineWithNegativePage()
	{
		/*
		$_REQUEST['page'] = -2;
		$result           = api_statuses_public_timeline('json');
		self::assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_statuses_public_timeline() function with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiStatusesPublicTimelineWithUnallowedUser()
	{
		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_statuses_public_timeline('json');
	}

	/**
	 * Test the api_statuses_public_timeline() function with an RSS result.
	 *
	 * @return void
	 */
	public function testApiStatusesPublicTimelineWithRss()
	{
		// $result = api_statuses_public_timeline('rss');
		// self::assertXml($result, 'statuses');
	}
}
