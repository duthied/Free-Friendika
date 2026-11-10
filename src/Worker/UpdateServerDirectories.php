<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\DI;
use Friendica\Model\GServer;

class UpdateServerDirectories
{
	/**
	 * Query global servers for their users
	 */
	public static function execute()
	{
		if (!DI::config()->get('system', 'poco_discovery')) {
			return;
		}

		GServer::discover();
	}
}
