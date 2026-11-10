<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Object\Api\Mastodon;

/**
 * Enumeration of order types that can be requested
 */
abstract class TimelineOrderByTypes
{
	public const CHANGED   = 'changed';
	public const CREATED   = 'created';
	public const COMMENTED = 'commented';
	public const EDITED    = 'edited';
	public const ID        = 'id';
	public const RECEIVED  = 'received';
}
