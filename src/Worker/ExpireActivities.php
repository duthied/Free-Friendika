<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;

class ExpireActivities
{
	/**
	 * Delete old post-activity entries
	 */
	public static function execute()
	{
		DBA::delete('post-activity', ["`received` < ?", DateTimeFormat::utc('now - 7 days')]);
	}
}
