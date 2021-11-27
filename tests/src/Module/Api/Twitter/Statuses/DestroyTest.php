<?php

namespace Friendica\Test\src\Module\Api\Twitter\Statuses;

use Friendica\Test\src\Module\Api\ApiTest;

class DestroyTest extends ApiTest
{
	/**
	 * Test the api_statuses_destroy() function.
	 *
	 * @return void
	 */
	public function testApiStatusesDestroy()
	{
		// $this->expectException(\Friendica\Network\HTTPException\BadRequestException::class);
		// api_statuses_destroy('json');
	}

	/**
	 * Test the api_statuses_destroy() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiStatusesDestroyWithoutAuthenticatedUser()
	{
		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// $_SESSION['authenticated'] = false;
		// api_statuses_destroy('json');
	}

	/**
	 * Test the api_statuses_destroy() function with an ID.
	 *
	 * @return void
	 */
	public function testApiStatusesDestroyWithId()
	{
		// DI::args()->setArgv(['', '', '', 1]);
		// $result = api_statuses_destroy('json');
		// self::assertStatus($result['status']);
	}
}
