<?php

namespace Friendica\Test\src\Module\Api\Twitter\Lists;

use Friendica\Test\src\Module\Api\ApiTest;

class StatusesTest extends ApiTest
{
	/**
	 * Test the api_lists_statuses() function.
	 *
	 * @return void
	 */
	public function testApiListsStatuses()
	{
		// $this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		// api_lists_statuses('json');
	}

	/**
	 * Test the api_lists_statuses() function with a list ID.
	 * @doesNotPerformAssertions
	 */
	public function testApiListsStatusesWithListId()
	{
		/*
		$_REQUEST['list_id'] = 1;
		$_REQUEST['page']    = -1;
		$_REQUEST['max_id']  = 10;
		$result              = api_lists_statuses('json');
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_lists_statuses() function with a list ID and a RSS result.
	 *
	 * @return void
	 */
	public function testApiListsStatusesWithListIdAndRss()
	{
		// $_REQUEST['list_id'] = 1;
		// $result              = api_lists_statuses('rss');
		// self::assertXml($result, 'statuses');
	}

	/**
	 * Test the api_lists_statuses() function with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiListsStatusesWithUnallowedUser()
	{
		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_lists_statuses('json');
	}
}
