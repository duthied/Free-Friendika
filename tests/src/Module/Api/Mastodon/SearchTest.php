<?php

namespace Friendica\Test\src\Module\Api\Mastodon;

use Friendica\Test\src\Module\Api\ApiTest;

class SearchTest extends ApiTest
{
	/**
	 * Test the api_search() function.
	 *
	 * @return void
	 */
	public function testApiSearch()
	{
		/*
		$_REQUEST['q']      = 'reply';
		$_REQUEST['max_id'] = 10;
		$result             = api_search('json');
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
			self::assertStringContainsStringIgnoringCase('reply', $status['text'], '', true);
		}
		*/
	}

	/**
	 * Test the api_search() function a count parameter.
	 *
	 * @return void
	 */
	public function testApiSearchWithCount()
	{
		/*
		$_REQUEST['q']     = 'reply';
		$_REQUEST['count'] = 20;
		$result            = api_search('json');
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
			self::assertStringContainsStringIgnoringCase('reply', $status['text'], '', true);
		}
		*/
	}

	/**
	 * Test the api_search() function with an rpp parameter.
	 *
	 * @return void
	 */
	public function testApiSearchWithRpp()
	{
		/*
		$_REQUEST['q']   = 'reply';
		$_REQUEST['rpp'] = 20;
		$result          = api_search('json');
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
			self::assertStringContainsStringIgnoringCase('reply', $status['text'], '', true);
		}
		*/
	}

	/**
	 * Test the api_search() function with an q parameter contains hashtag.
	 * @doesNotPerformAssertions
	 */
	public function testApiSearchWithHashtag()
	{
		/*
		$_REQUEST['q'] = '%23friendica';
		$result        = api_search('json');
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
			self::assertStringContainsStringIgnoringCase('#friendica', $status['text'], '', true);
		}
		*/
	}

	/**
	 * Test the api_search() function with an exclude_replies parameter.
	 * @doesNotPerformAssertions
	 */
	public function testApiSearchWithExcludeReplies()
	{
		/*
		$_REQUEST['max_id']          = 10;
		$_REQUEST['exclude_replies'] = true;
		$_REQUEST['q']               = 'friendica';
		$result                      = api_search('json');
		foreach ($result['status'] as $status) {
			self::assertStatus($status);
		}
		*/
	}

	/**
	 * Test the api_search() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiSearchWithUnallowedUser()
	{
		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// api_search('json');
	}

	/**
	 * Test the api_search() function without any GET query parameter.
	 *
	 * @return void
	 */
	public function testApiSearchWithoutQuery()
	{
		// $this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		// api_search('json');
	}
}
