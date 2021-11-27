<?php

namespace Friendica\Test\src\Module\Api\Twitter;

use Friendica\Test\src\Module\Api\ApiTest;

class FavoritesTest extends ApiTest
{
	/**
	 * Test the api_favorites() function.
	 *
	 * @return void
	 */
	public function testApiFavorites()
	{
		/*
		$_REQUEST['page']   = -1;
		$_REQUEST['max_id'] = 10;
		$result             = api_favorites('json');
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_favorites() function with an RSS result.
	 *
	 * @return void
	 */
	public function testApiFavoritesWithRss()
	{
		// $result = api_favorites('rss');
		// self::assertXml($result, 'statuses');
	}

	/**
	 * Test the api_favorites() function with an unallowed user.
	 *
	 * @return void
	 */
	public function testApiFavoritesWithUnallowedUser()
	{
		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_favorites('json');
	}
}
