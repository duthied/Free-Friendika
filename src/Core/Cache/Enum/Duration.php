<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Cache\Enum;

/**
 * Enumeration for cache durations
 */
abstract class Duration
{
	public const MONTH        = 2592000;
	public const HOUR         = 3600;
	public const HALF_HOUR    = 1800;
	public const QUARTER_HOUR = 900;
	public const MINUTE       = 60;
	public const WEEK         = 604800;
	public const INFINITE     = 0;
	public const DAY          = 86400;
	public const FIVE_MINUTES = 300;
}
