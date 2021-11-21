<?php

namespace Friendica\Test\src\Module\Api\GnuSocial\Help;

use Friendica\DI;
use Friendica\Module\Api\GNUSocial\Help\Test;
use Friendica\Test\src\Module\Api\ApiTest;

class TestTest extends ApiTest
{
	public function testJson()
	{
		$test = new Test(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']);
		$response = $test->run();

		self::assertEquals('"ok"', $response->getContent());
	}

	public function testXml()
	{
		$test = new Test(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'xml']);
		$response = $test->run();

		self::assertxml($response->getContent(), 'ok');
	}
}
