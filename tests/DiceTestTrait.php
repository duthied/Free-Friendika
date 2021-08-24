<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Test;

use Friendica\DI;
use Friendica\Network\HTTPClient;
use Friendica\Network\IHTTPClient;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use mattwright\URLResolver;

/**
 * This class mocks some DICE dependencies because they're not direct usable for test environments
 * (Like fetching data from external endpoints)
 */
trait DiceTestTrait
{
	/**
	 * Handler for mocking requests anywhere for testing purpose
	 *
	 * @var HandlerStack
	 */
	protected static $httpRequestHandler;

	protected static function setUpDice(): void
	{
		if (!empty(self::$httpRequestHandler) && self::$httpRequestHandler instanceof HandlerStack) {
			return;
		}

		self::$httpRequestHandler = HandlerStack::create();

		$client = new Client(['handler' => self::$httpRequestHandler]);

		$resolver = \Mockery::mock(URLResolver::class);

		$httpClient = new HTTPClient(DI::logger(), DI::profiler(), $client, $resolver);

		$dice    = DI::getDice();
		$newDice = \Mockery::mock($dice)->makePartial();
		$newDice->shouldReceive('create')->with(IHTTPClient::class)->andReturn($httpClient);
		DI::init($newDice);
	}

	protected function tearDown() : void
	{
		\Mockery::close();

		parent::tearDown();
	}
}
