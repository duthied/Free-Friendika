<?php

namespace Friendica\Test\src\Module\Api\Mastodon\Accounts;

use Friendica\Test\src\Module\Api\ApiTest;

class VerifyCredentialsTest extends ApiTest
{
	/**
	 * Test the api_account_verify_credentials() function.
	 *
	 * @return void
	 */
	public function testApiAccountVerifyCredentials()
	{
		// self::assertArrayHasKey('user', api_account_verify_credentials('json'));
	}

	/**
	 * Test the api_account_verify_credentials() function without an authenticated user.
	 *
	 * @return void
	 */
	public function testApiAccountVerifyCredentialsWithoutAuthenticatedUser()
	{
		// $this->expectException(\Friendica\Network\HTTPException\UnauthorizedException::class);
		// BasicAuth::setCurrentUserID();
		// $_SESSION['authenticated'] = false;
		// api_account_verify_credentials('json');
	}
}
