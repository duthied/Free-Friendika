<?php

namespace Friendica\Test\src\Module;

use Friendica\Test\src\Module\Api\ApiTest;

class BaseApiTest extends ApiTest
{
	public function withWrongAuth()
	{
		self::markTestIncomplete('Needs BasicAuth as dynamic method for overriding first');

		/*
		global $API;
		$API['api_path']           = [
			'method' => 'method',
			'auth'   => true
		];
		$_SESSION['authenticated'] = false;
		$_SERVER['REQUEST_METHOD'] = 'method';
		$_SERVER['QUERY_STRING'] = 'pagename=api_path';

		$args = DI::args()->determine($_SERVER, $_GET);

		self::assertEquals(
			'{"status":{"error":"This API requires login","code":"401 Unauthorized","request":"api_path"}}',
			api_call($this->app, $args)
		);
		*/
	}
}
