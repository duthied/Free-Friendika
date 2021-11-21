<?php

namespace Friendica\Test\src\Module\Api\Twitter\Account;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Account\RateLimitStatus;
use Friendica\Test\src\Module\Api\ApiTest;

class RateLimitStatusTest extends ApiTest
{
	public function testWithJson()
	{
		$rateLimitStatus = new RateLimitStatus(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']);
		$response = $rateLimitStatus->run();

		$result = json_decode($response->getBody());

		self::assertEquals(['Content-type' => ['application/json'], ICanCreateResponses::X_HEADER => ['json']], $response->getHeaders());
		self::assertEquals(150, $result->remaining_hits);
		self::assertEquals(150, $result->hourly_limit);
		self::assertIsInt($result->reset_time_in_seconds);
	}

	public function testWithXml()
	{
		$rateLimitStatus = new RateLimitStatus(DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'xml']);
		$response = $rateLimitStatus->run();

		self::assertEquals(['Content-type' => ['text/xml'], ICanCreateResponses::X_HEADER => ['xml']], $response->getHeaders());
		self::assertXml($response->getBody(), 'hash');
	}
}
