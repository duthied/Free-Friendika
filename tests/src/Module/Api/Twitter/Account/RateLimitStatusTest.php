<?php

namespace Friendica\Test\src\Module\Api\Twitter\Account;

use Friendica\Module\Api\Twitter\Account\RateLimitStatus;
use Friendica\Test\src\Module\Api\ApiTest;
use Friendica\Test\Util\ApiResponseDouble;

class RateLimitStatusTest extends ApiTest
{
	public function testWithJson()
	{
		RateLimitStatus::rawContent(['extension' => 'json']);

		$result = json_decode(ApiResponseDouble::getOutput());

		self::assertEquals(150, $result->remaining_hits);
		self::assertEquals(150, $result->hourly_limit);
		self::assertIsInt($result->reset_time_in_seconds);
	}

	public function testWithXml()
	{
		RateLimitStatus::rawContent(['extension' => 'xml']);

		self::assertXml(ApiResponseDouble::getOutput(), 'hash');
	}
}
