<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Update;
use Friendica\DI;

/**
 * This file is called when the database structure needs to be updated
 */
class DBUpdate
{
	public static function execute()
	{
		// Just in case the last update wasn't failed
		if (DI::config()->get('system', 'update', Update::SUCCESS) != Update::FAILED) {
			Update::run(DI::appHelper()->getBasePath());
		}
	}
}
