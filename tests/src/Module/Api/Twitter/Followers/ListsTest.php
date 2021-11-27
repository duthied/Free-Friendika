<?php

namespace Friendica\Test\src\Module\Api\Twitter\Followers;

use Friendica\Test\src\Module\Api\ApiTest;

class ListsTest extends ApiTest
{
	/**
	 * Test the api_statuses_f() function.
	 *
	 * @return void
	 */
	public function testApiStatusesFWithFollowers()
	{
		// $result = api_statuses_f('followers');
		// self::assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_statuses_followers() function.
	 *
	 * @return void
	 */
	public function testApiStatusesFollowers()
	{
		// $result = api_statuses_followers('json');
		// self::assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_statuses_followers() function an undefined cursor GET variable.
	 *
	 * @return void
	 */
	public function testApiStatusesFollowersWithUndefinedCursor()
	{
		// $_GET['cursor'] = 'undefined';
		// self::assertFalse(api_statuses_followers('json'));
	}
}
