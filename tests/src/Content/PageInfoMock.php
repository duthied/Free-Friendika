<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Content;

use Friendica\Content\PageInfo;

/**
 * Class PageInfoMock
 *
 * Exposes protected methods for test in the inherited class
 *
 * @method static string|null getRelevantUrlFromBody(string $body, $searchNakedUrls = false)
 * @method static string stripTrailingUrlFromBody(string $body, string $url)
 */
class PageInfoMock extends PageInfo
{
	public static function __callStatic($name, $arguments)
	{
		return self::$name(...$arguments);
	}
}
