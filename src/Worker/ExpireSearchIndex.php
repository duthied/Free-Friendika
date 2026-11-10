<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Model\Post;

/**
 * Expire old search index entries
 */
class ExpireSearchIndex
{
	public static function execute($param = '', $hook_function = '')
	{
		Post\SearchIndex::expire();
	}
}
