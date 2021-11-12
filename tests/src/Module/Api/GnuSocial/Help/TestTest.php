<?php

namespace Friendica\Test\src\Module\Api\GnuSocial\Help;

use Friendica\Module\Api\GNUSocial\Help\Test;
use Friendica\Test\src\Module\Api\ApiTest;
use Friendica\Test\Util\ApiResponseDouble;

class TestTest extends ApiTest
{
	public function testJson()
	{
		Test::rawContent(['extension' => 'json']);

		self::assertEquals('"ok"', ApiResponseDouble::getOutput());
	}

	public function testXml()
	{
		Test::rawContent(['extension' => 'xml']);

		self::assertxml(ApiResponseDouble::getOutput(), 'ok');
	}
}
