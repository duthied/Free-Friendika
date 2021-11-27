<?php

namespace Friendica\Test\src\Module\Api\Twitter\Statuses;

use Friendica\Test\src\Module\Api\ApiTest;

class ShowTest extends ApiTest
{
	/**
	 * Test the api_statuses_show() function.
	 *
	 * @return void
	 */
	public function testApiStatusesShow()
	{
		// $this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		// api_statuses_show('json');
	}

	/**
	 * Test the api_statuses_show() function with an ID.
	 *
	 * @return void
	 */
	public function testApiStatusesShowWithId()
	{
		// DI::args()->setArgv(['', '', '', 1]);
		// $result = api_statuses_show('json');
		// self::assertStatus($result['status']);
	}

	/**
	 * Test the api_statuses_show() function with the conversation parameter.
	 *
	 * @return void
	 */
	public function testApiStatusesShowWithConversation()
	{
		/*
		DI::args()->setArgv(['', '', '', 1]);
		$_REQUEST['conversation'] = 1;
		$result                   = api_statuses_show('json');
		self::assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_statuses_show() function with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiStatusesShowWithUnallowedUser()
	{
		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_statuses_show('json');
	}
}
