<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Util\DateTimeFormat;

/**
 * Update contact suggestions for all active users
 */
class UpdateAllSuggestions
{
	public static function execute()
	{
		$users = DBA::select('user', ['uid'], ["`last-activity` > ? AND `uid` > ?", DateTimeFormat::utc('now - 3 days', 'Y-m-d'), 0]);
		while ($user = DBA::fetch($users)) {
			Contact\Relation::updateCachedSuggestions($user['uid']);
		}
		DBA::close($users);
	}
}
