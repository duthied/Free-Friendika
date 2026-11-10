<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test;

use Dice\Dice;
use Friendica\DI;
use Friendica\Network\HTTPClient\Factory\HttpClient;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use GuzzleHttp\HandlerStack;

/**
 * This class injects a mockable handler into the IHTTPClient dependency per Dice
 */
trait DiceHttpMockHandlerTrait
{
	use FixtureTestTrait;

	/**
	 * Handler for mocking requests anywhere for testing purpose
	 *
	 * @var HandlerStack
	 */
	protected $httpRequestHandler;

	protected function setupHttpMockHandler(): void
	{
		$this->setUpFixtures();

		$this->httpRequestHandler = HandlerStack::create();

		$dice = DI::getDice();
		// addRule() clones the current instance and returns a new one, so no concurrency problems :-)
		$newDice = $dice->addRule(ICanSendHttpRequests::class, [
			'instanceOf' => HttpClient::class,
			'call'       => [
				['createClient', [$this->httpRequestHandler], Dice::CHAIN_CALL],
			],
		]);

		DI::init($newDice);
	}

	protected function tearDownHandler(): void
	{
		$this->tearDownFixtures();
	}
}
