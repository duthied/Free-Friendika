<?php

namespace Friendica\Test\src\Module\Api\GnuSocial\Help;

use Friendica\DI;
use Friendica\Module\Api\GNUSocial\Help\Test;
use Friendica\Test\src\Module\Api\ApiTest;
use Friendica\Test\Util\ApiResponseDouble;

class TestTest extends ApiTest
{
	public function testJson()
	{
		$test = new Test(DI::l10n(), ['extension' => 'json']);
		$test->rawContent();

		self::assertEquals('"ok"', ApiResponseDouble::getOutput());
	}

	public function testXml()
	{
		$test = new Test(DI::l10n(), ['extension' => 'xml']);
		$test->rawContent();

		self::assertxml(ApiResponseDouble::getOutput(), 'ok');
	}
}
