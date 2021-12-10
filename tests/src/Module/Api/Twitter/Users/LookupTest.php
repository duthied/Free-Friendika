<?php

namespace Friendica\Test\src\Module\Api\Twitter\Users;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Users\Lookup;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Test\src\Module\Api\ApiTest;

class LookupTest extends ApiTest
{
	/**
	 * Test the api_users_lookup() function.
	 *
	 * @return void
	 */
	public function testApiUsersLookup()
	{
		$this->expectException(NotFoundException::class);

		$lookup = new Lookup(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET]);
		$lookup->run();
	}

	/**
	 * Test the api_users_lookup() function with an user ID.
	 *
	 * @return void
	 */
	public function testApiUsersLookupWithUserId()
	{
		$lookup = new Lookup(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), ['REQUEST_METHOD' => Router::GET]);
		$respone = $lookup->run(['user_id' => static::OTHER_USER['id']]);

		$json = $this->toJson($respone);

		self::assertOtherUser($json[0]);
	}
}
