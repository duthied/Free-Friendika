<?php

namespace Friendica\Test\src\Module\Api\GnuSocial\GnuSocial;

use Friendica\DI;
use Friendica\Module\Api\GNUSocial\GNUSocial\Version;
use Friendica\Test\src\Module\Api\ApiTest;

class VersionTest extends ApiTest
{
	public function test()
	{
		$version = new Version(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']);
		$response = $version->run();

		self::assertEquals('"0.9.7"', $response->getContent());
	}
}
