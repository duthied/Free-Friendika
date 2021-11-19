<?php

namespace Friendica\Test\src\Module\Api\Twitter\Account;

use Friendica\Module\Api\Twitter\Account\RateLimitStatus;
use Friendica\Test\src\Module\Api\ApiTest;
use Friendica\Test\Util\ApiResponseDouble;

class RateLimitStatusTest extends ApiTest
{
	public function testWithJson()
	{
		$rateLimitStatus = new RateLimitStatus(['extension' => 'json']);
		$rateLimitStatus->rawContent();

		$result = json_decode(ApiResponseDouble::getOutput());

		self::assertEquals(150, $result->remaining_hits);
		self::assertEquals(150, $result->hourly_limit);
		self::assertIsInt($result->reset_time_in_seconds);
	}

	public function testWithXml()
	{
		$rateLimitStatus = new RateLimitStatus(['extension' => 'xml']);
		$rateLimitStatus->rawContent();

		self::assertXml(ApiResponseDouble::getOutput(), 'hash');
	}
}
