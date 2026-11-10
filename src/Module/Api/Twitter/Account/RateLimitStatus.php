<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\Account;

use Friendica\Module\BaseApi;
use Friendica\Util\DateTimeFormat;

/**
 * API endpoint: /api/account/rate_limit_status
 */
class RateLimitStatus extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		if (($this->parameters['extension'] ?? '') == 'xml') {
			$hash = [
				'remaining-hits'        => '150',
				'@attributes'           => ['type' => 'integer'],
				'hourly-limit'          => '150',
				'@attributes2'          => ['type' => 'integer'],
				'reset-time'            => DateTimeFormat::utc('now + 1 hour', DateTimeFormat::ATOM),
				'@attributes3'          => ['type' => 'datetime'],
				'reset_time_in_seconds' => strtotime('now + 1 hour'),
				'@attributes4'          => ['type' => 'integer'],
			];
		} else {
			$hash = [
				'reset_time_in_seconds' => strtotime('now + 1 hour'),
				'remaining_hits'        => '150',
				'hourly_limit'          => '150',
				'reset_time'            => DateTimeFormat::utc('now + 1 hour', DateTimeFormat::API),
			];
		}

		$this->response->addFormattedContent('hash', ['hash' => $hash], $this->parameters['extension'] ?? null);
	}
}
