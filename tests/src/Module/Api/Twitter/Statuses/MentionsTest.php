<?php

namespace Friendica\Test\src\Module\Api\Twitter\Statuses;

use Friendica\Test\src\Module\Api\ApiTest;

class MentionsTest extends ApiTest
{
	/**
	 * Test the api_statuses_mentions() function.
	 *
	 * @return void
	 */
	public function testApiStatusesMentions()
	{
		/*
		$this->app->setLoggedInUserNickname($this->selfUser['nick']);
		$_REQUEST['max_id'] = 10;
		$result             = api_statuses_mentions('json');
		self::assertEmpty($result['status']);
		// We should test with mentions in the database.
		*/
	}

	/**
	 * Test the api_statuses_mentions() function with a negative page parameter.
	 *
	 * @return void
	 */
	public function testApiStatusesMentionsWithNegativePage()
	{
		// $_REQUEST['page'] = -2;
		// $result           = api_statuses_mentions('json');
		// self::assertEmpty($result['status']);
	}

	/**
	 * Test the api_statuses_mentions() function with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiStatusesMentionsWithUnallowedUser()
	{
		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_statuses_mentions('json');
	}

	/**
	 * Test the api_statuses_mentions() function with an RSS result.
	 *
	 * @return void
	 */
	public function testApiStatusesMentionsWithRss()
	{
		// $result = api_statuses_mentions('rss');
		// self::assertXml($result, 'statuses');
	}
}
