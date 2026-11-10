<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Database\DBA;
use Friendica\Model\Photo;

/**
 * Update the cached values for the number of photo albums per user
 */
class UpdatePhotoAlbums
{
	public static function execute()
	{
		$users = DBA::select('user', ['uid'], ['verified' => true, 'blocked' => false, 'account_removed' => false, 'account_expired' => false]);
		while ($user = DBA::fetch($users)) {
			Photo::clearAlbumCache($user['uid']);
		}
		DBA::close($users);
	}
}
