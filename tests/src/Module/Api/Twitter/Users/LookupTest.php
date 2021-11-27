<?php

namespace Friendica\Test\src\Module\Api\Twitter\Users;

use Friendica\Test\src\Module\Api\ApiTest;

class LookupTest extends ApiTest
{
	/**
	 * Test the api_users_lookup() function.
	 *
	 * @return void
	 */
	public function testApiUsersLookup()
	{
		// $this->expectException(\Friendica\Network\HTTPException\NotFoundException::class);
		// api_users_lookup('json');
	}

	/**
	 * Test the api_users_lookup() function with an user ID.
	 *
	 * @return void
	 */
	public function testApiUsersLookupWithUserId()
	{
		// $_REQUEST['user_id'] = $this->otherUser['id'];
		// $result              = api_users_lookup('json');
		// self::assertOtherUser($result['users'][0]);
	}
}
