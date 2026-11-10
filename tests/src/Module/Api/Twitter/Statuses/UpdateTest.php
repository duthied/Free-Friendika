<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Module\Api\Twitter\Statuses;

use Friendica\App\Router;
use Friendica\DI;
use Friendica\Module\Api\Twitter\Statuses\Update;
use Friendica\Test\ApiTestCase;

class UpdateTest extends ApiTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$this->useHttpMethod(Router::POST);
	}

	/**
	 * Test the api_statuses_update() function.
	 *
	 * @return void
	 */
	public function testApiStatusesUpdate(): void
	{
		$_FILES = [
			'media' => [
				'id'       => 666,
				'size'     => 666,
				'width'    => 666,
				'height'   => 666,
				'tmp_name' => $this->getTempImage(),
				'name'     => 'spacer.png',
				'type'     => 'image/png',
			],
		];

		// @phpstan-ignore method.deprecated
		$response = (new Update(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'status'                => 'Status content #friendica',
				'in_reply_to_status_id' => 0,
				'lat'                   => 48,
				'long'                  => 7,
			]);

		$json = $this->toJson($response);

		self::assertStatus($json);
		self::assertStringContainsString('Status content #friendica', $json->text);
		self::assertStringContainsString('Status content #', $json->statusnet_html);
	}

	/**
	 * Test the api_statuses_update() function with an HTML status.
	 *
	 * @return void
	 */
	public function testApiStatusesUpdateWithHtml(): void
	{
		// @phpstan-ignore method.deprecated
		$response = (new Update(DI::mstdnError(), DI::appHelper(), DI::l10n(), DI::baseUrl(), DI::args(), DI::logger(), DI::profiler(), DI::apiResponse(), []))
			->run($this->httpExceptionMock, [
				'htmlstatus' => '<b>Status content</b>',
			]);

		$json = $this->toJson($response);

		self::assertStatus($json);
	}
}
