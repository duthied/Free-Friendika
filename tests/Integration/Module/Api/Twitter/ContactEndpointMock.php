<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Integration\Module\Api\Twitter;

use Friendica\Module\Api\Twitter\ContactEndpoint;

/**
 * Class ContactEndpointMock
 *
 * Exposes protected methods for test in the inherited class
 *
 * @method static int   getUid(int $contact_id = null, string $screen_name = null)
 * @method static array list(array $ids, int $total_count, int $uid, int $cursor = -1, int $count = self::DEFAULT_COUNT, bool $skip_status = false, bool $include_user_entities = true)
 * @method static array ids(array $ids, int $total_count, int $cursor = -1, int $count = self::DEFAULT_COUNT, bool $stringify_ids = false)
 *
 * @package Friendica\Test\Integration\Module\Api\Twitter
 */
class ContactEndpointMock extends ContactEndpoint
{
	public static function __callStatic($name, $arguments)
	{
		return self::$name(...$arguments);
	}
}
