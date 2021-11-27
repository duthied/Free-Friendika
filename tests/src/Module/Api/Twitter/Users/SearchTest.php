<?php

namespace Friendica\Test\src\Module\Api\Twitter\Users;

use Friendica\Test\src\Module\Api\ApiTest;

class SearchTest extends ApiTest
{
	/**
	 * Test the api_users_search() function.
	 *
	 * @return void
	 */
	public function testApiUsersSearch()
	{
		// $_GET['q'] = 'othercontact';
		// $result    = api_users_search('json');
		// self::assertOtherUser($result['users'][0]);
	}

	/**
	 * Test the api_users_search() function with an XML result.
	 *
	 * @return void
	 */
	public function testApiUsersSearchWithXml()
	{
		// $_GET['q'] = 'othercontact';
		// $result    = api_users_search('xml');
		// self::assertXml($result, 'users');
	}

	/**
	 * Test the api_users_search() function without a GET q parameter.
	 *
	 * @return void
	 */
	public function testApiUsersSearchWithoutQuery()
	{
		// $this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		// api_users_search('json');
	}
}
