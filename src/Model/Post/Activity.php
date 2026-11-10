<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;

class Activity
{
	/**
	 * Insert a new post-activity entry
	 *
	 * @return bool   success
	 */
	public static function insert(int $uri_id, string $source): bool
	{
		// Additionally assign the key fields
		$fields = [
			'uri-id'   => $uri_id,
			'activity' => $source,
			'received' => DateTimeFormat::utcNow(),
		];

		return DBA::insert('post-activity', $fields, Database::INSERT_IGNORE);
	}

	/**
	 * Retrieves activity of the given uri-id
	 *
	 * @param int   $uriId
	 *
	 * @return array
	 */
	public static function getByURIId(int $uriId): array
	{
		$activity = DBA::selectFirst('post-activity', [], ['uri-id' => $uriId]);
		return json_decode($activity['activity'] ?? '', true) ?? [];
	}

	/**
	 * Checks if the given uridid has a stored activity
	 *
	 * @param integer $uriId
	 *
	 * @return boolean
	 */
	public static function exists(int $uriId): bool
	{
		return DBA::exists('post-activity', ['uri-id' => $uriId]);
	}
}
