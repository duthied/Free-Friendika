<?php

namespace Friendica\Test\src\Module\Api\GnuSocial\GnuSocial;

use Friendica\Module\Api\GNUSocial\GNUSocial\Version;
use Friendica\Test\src\Module\Api\ApiTest;
use Friendica\Test\Util\ApiResponseDouble;

class VersionTest extends ApiTest
{
	public function test()
	{
		$version = new Version(['extension' => 'json']);
		$version->rawContent();

		$result = json_decode(ApiResponseDouble::getOutput());

		self::assertEquals('0.9.7', $result);
	}
}
