<?php

namespace Friendica\Test\src\Module\Api\Twitter;

use Friendica\Module\Api\Twitter\SavedSearches;
use Friendica\Test\src\Module\Api\ApiTest;
use Friendica\Test\Util\ApiResponseDouble;

class SavedSearchesTest extends ApiTest
{
	public function test()
	{
		$savedSearch = new SavedSearches(['extension' => 'json']);
		$savedSearch->rawContent();

		$result = json_decode(ApiResponseDouble::getOutput());

		self::assertEquals(1, $result[0]->id);
		self::assertEquals(1, $result[0]->id_str);
		self::assertEquals('Saved search', $result[0]->name);
		self::assertEquals('Saved search', $result[0]->query);
	}
}
