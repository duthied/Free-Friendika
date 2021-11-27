<?php

namespace Friendica\Test\src\Module\Api\Twitter\Friends;

use Friendica\Test\src\Module\Api\ApiTest;

class ListsTest extends ApiTest
{
	/**
	 * Test the api_statuses_f() function.
	 *
	 * @return void
	 */
	public function testApiStatusesFWithFriends()
	{
		// $_GET['page'] = -1;
		// $result       = api_statuses_f('friends');
		// self::assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_statuses_f() function an undefined cursor GET variable.
	 *
	 * @return void
	 */
	public function testApiStatusesFWithUndefinedCursor()
	{
		// $_GET['cursor'] = 'undefined';
		// self::assertFalse(api_statuses_f('friends'));
	}

	/**
	 * Test the api_statuses_friends() function.
	 *
	 * @return void
	 */
	public function testApiStatusesFriends()
	{
		// $result = api_statuses_friends('json');
		// self::assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_statuses_friends() function an undefined cursor GET variable.
	 *
	 * @return void
	 */
	public function testApiStatusesFriendsWithUndefinedCursor()
	{
		// $_GET['cursor'] = 'undefined';
		// self::assertFalse(api_statuses_friends('json'));
	}
}
