<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Database\DBA;

class RemoveUnusedTags
{
	/**
	 * Delete tag entries that aren't used anymore
	 */
	public static function execute()
	{
		DBA::delete('tag', ["NOT `id` IN (SELECT `tid` FROM `post-category` WHERE `tid` = `tag`.`id`) AND NOT `id` IN (SELECT `tid` FROM `post-tag` WHERE `tid` = `tag`.`id`)"]);
	}
}
