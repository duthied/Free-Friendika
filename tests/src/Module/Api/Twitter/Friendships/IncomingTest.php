<?php

namespace Friendica\Test\src\Module\Api\Twitter\Friendships;

use Friendica\Test\src\Module\Api\ApiTest;

class IncomingTest extends ApiTest
{
	/**
	 * Test the api_friendships_incoming() function.
	 *
	 * @return void
	 */
	public function testApiFriendshipsIncoming()
	{
		// $result = api_friendships_incoming('json');
		// self::assertArrayHasKey('id', $result);
	}

	/**
	 * Test the api_friendships_incoming() function an undefined cursor GET variable.
	 *
	 * @return void
	 */
	public function testApiFriendshipsIncomingWithUndefinedCursor()
	{
		// $_GET['cursor'] = 'undefined';
		// self::assertFalse(api_friendships_incoming('json'));
	}
}
