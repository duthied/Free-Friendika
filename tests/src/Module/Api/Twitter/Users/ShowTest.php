<?php

namespace Friendica\Test\src\Module\Api\Twitter\Users;

use Friendica\Test\src\Module\Api\ApiTest;

class ShowTest extends ApiTest
{
	/**
	 * Test the api_users_show() function.
	 *
	 * @return void
	 */
	public function testApiUsersShow()
	{
		/*
		$result = api_users_show('json');
		// We can't use assertSelfUser() here because the user object is missing some properties.
		self::assertEquals($this->selfUser['id'], $result['user']['cid']);
		self::assertEquals('DFRN', $result['user']['location']);
		self::assertEquals($this->selfUser['name'], $result['user']['name']);
		self::assertEquals($this->selfUser['nick'], $result['user']['screen_name']);
		self::assertTrue($result['user']['verified']);
		*/
	}

	/**
	 * Test the api_users_show() function with an XML result.
	 *
	 * @return void
	 */
	public function testApiUsersShowWithXml()
	{
		// $result = api_users_show('xml');
		// self::assertXml($result, 'statuses');
	}
}
