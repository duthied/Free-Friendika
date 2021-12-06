<?php

namespace Friendica\Test\src\Module\Api\Mastodon;

use Friendica\Test\src\Module\Api\ApiTest;

class ConversationsTest extends ApiTest
{
	/**
	 * Test the api_conversation_show() function.
	 *
	 * @return void
	 */
	public function testApiConversationShow()
	{
		self::markTestIncomplete('Needs Conversations to not set header during call (like at BaseApi::setLinkHeader');

		// $this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		// api_conversation_show('json');
	}

	/**
	 * Test the api_conversation_show() function with an ID.
	 *
	 * @return void
	 */
	public function testApiConversationShowWithId()
	{
		self::markTestIncomplete('Needs Conversations to not set header during call (like at BaseApi::setLinkHeader');

		/*
		DI::args()->setArgv(['', '', '', 1]);
		$_REQUEST['max_id'] = 10;
		$_REQUEST['page']   = -2;
		$result             = api_conversation_show('json');
		self::assertNotEmpty($result['status']);
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_conversation_show() function with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiConversationShowWithUnallowedUser()
	{
		self::markTestIncomplete('Needs Conversations to not set header during call (like at BaseApi::setLinkHeader');

		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_conversation_show('json');
	}
}
