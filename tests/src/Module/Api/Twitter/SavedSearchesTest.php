<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Module\Api\Twitter;

use Friendica\Capabilities\ICanCreateResponses;
use Friendica\DI;
use Friendica\Module\Api\Twitter\SavedSearches;
use Friendica\Test\src\Module\Api\ApiTest;

class SavedSearchesTest extends ApiTest
{
	public function test()
	{
		$response = (new SavedSearches(DI::mstdnError(), DI::app(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['extension' => 'json']))
			->run($this->httpExceptionMock);

		$result = $this->toJson($response);

		self::assertEquals(['Content-type' => ['application/json'], ICanCreateResponses::X_HEADER => ['json']], $response->getHeaders());
		self::assertEquals(1, $result[0]->id);
		self::assertEquals(1, $result[0]->id_str);
		self::assertEquals('Saved search', $result[0]->name);
		self::assertEquals('Saved search', $result[0]->query);
	}
}
