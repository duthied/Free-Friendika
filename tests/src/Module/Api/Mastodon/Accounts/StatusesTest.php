<?php

namespace Friendica\Test\src\Module\Api\Mastodon\Accounts;

use Friendica\Test\src\Module\Api\ApiTest;

class StatusesTest extends ApiTest
{
	/**
	 * Test the api_status_show() function.
	 */
	public function testApiStatusShowWithJson()
	{
		self::markTestIncomplete('Needs Statuses to not set header during call (like at BaseApi::setLinkHeader');

		// $result = api_status_show('json', 1);
		// self::assertStatus($result['status']);
	}

	/**
	 * Test the api_status_show() function with an XML result.
	 */
	public function testApiStatusShowWithXml()
	{
		self::markTestIncomplete('Needs Statuses to not set header during call (like at BaseApi::setLinkHeader');

		// $result = api_status_show('xml', 1);
		// self::assertXml($result, 'statuses');
	}
}
