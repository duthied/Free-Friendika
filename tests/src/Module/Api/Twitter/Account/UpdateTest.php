<?php

namespace Friendica\Test\src\Module\Api\Twitter\Account;

use Friendica\Test\src\Module\Api\ApiTest;

class UpdateTest extends ApiTest
{
	/**
	 * Test the api_account_update_profile() function.
	 *
	 * @return void
	 */
	public function testApiAccountUpdateProfile()
	{
		/*
		$_POST['name']        = 'new_name';
		$_POST['description'] = 'new_description';
		$result               = api_account_update_profile('json');
		// We can't use assertSelfUser() here because the user object is missing some properties.
		self::assertEquals($this->selfUser['id'], $result['user']['cid']);
		self::assertEquals('DFRN', $result['user']['location']);
		self::assertEquals($this->selfUser['nick'], $result['user']['screen_name']);
		self::assertEquals('new_name', $result['user']['name']);
		self::assertEquals('new_description', $result['user']['description']);
		*/
	}
}
