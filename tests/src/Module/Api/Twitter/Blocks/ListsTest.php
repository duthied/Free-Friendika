<?php

namespace Friendica\Test\src\Module\Api\Twitter\Blocks;

use Friendica\Test\src\Module\Api\ApiTest;

class ListsTest extends ApiTest
{
	/**
	 * Test the api_statuses_f() function.
	 *
	 * @return void
	 */
	public function testApiStatusesFWithBlocks()
	{
		// $result = api_statuses_f('blocks');
		// self::assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_blocks_list() function.
	 *
	 * @return void
	 */
	public function testApiBlocksList()
	{
		// $result = api_blocks_list('json');
		// self::assertArrayHasKey('user', $result);
	}

	/**
	 * Test the api_blocks_list() function an undefined cursor GET variable.
	 *
	 * @return void
	 */
	public function testApiBlocksListWithUndefinedCursor()
	{
		// $_GET['cursor'] = 'undefined';
		// self::assertFalse(api_blocks_list('json'));
	}
}
