<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Integration\Module\Api\Mastodon\Accounts;

use Friendica\Core\EarlyExitException;
use Friendica\DI;
use Friendica\Module\Api\Mastodon\Accounts\Statuses;
use Friendica\Test\ApiTestCase;
use GuzzleHttp\Psr7\ServerRequest;

final class StatusesTest extends ApiTestCase
{
	public function testApiStatusShowWithJson(): void
	{
		$module = $this->createModule(43);

		$request = new ServerRequest('GET', 'https://friendica.local/api/v1/accounts/43/statuses');

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$statuses = $this->toJson($e->getResponse());

			self::assertNotEmpty($statuses);
			self::assertCount(4, $statuses);
			self::assertEquals(['5', '3', '2', '1'], array_column($statuses, 'id'));
			self::assertObjectHasProperty('id', $statuses[0]);
		}
	}

	public function testApiStatusShowWithXml(): void
	{
		$module = $this->createModule(45);

		$request = new ServerRequest('GET', 'https://friendica.local/api/v1/accounts/45/statuses');

		try {
			$module->handleRequest($request);
			self::fail('Expected EarlyExitException');
		} catch (EarlyExitException $e) { // @phpstan-ignore catch.neverThrown
			$statuses = $this->toJson($e->getResponse());

			self::assertNotEmpty($statuses);
			self::assertCount(4, $statuses);
			self::assertEquals(['100', '7', '6', '4'], array_column($statuses, 'id'));
			self::assertObjectHasProperty('id', $statuses[0]);
		}
	}

	private function createModule(int $contactId): Statuses
	{
		return new Statuses(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), [], ['id' => (string) $contactId]);
	}
}
