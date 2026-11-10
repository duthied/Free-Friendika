<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\Post;

use Friendica\Database\DBA;
use BadMethodCallException;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Protocol\ActivityPub;

class Collection
{
	public const FEATURED = 0;

	/**
	 * Add a post to a collection
	 *
	 * @param integer $uri_id
	 * @param integer $type
	 * @param integer $author_id
	 * @param integer $cache_uid If set to a non zero value, the featured cache is cleared
	 */
	public static function add(int $uri_id, int $type, int $author_id, int $cache_uid = 0)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		DBA::insert('post-collection', ['uri-id' => $uri_id, 'type' => $type, 'author-id' => $author_id], Database::INSERT_IGNORE);

		if (!empty($cache_uid) && ($type == self::FEATURED)) {
			DI::cache()->delete(ActivityPub\Transmitter::CACHEKEY_FEATURED . $cache_uid);
		}
	}

	/**
	 * Remove a post from a collection
	 *
	 * @param integer $uri_id
	 * @param integer $type
	 * @param integer $cache_uid If set to a non zero value, the featured cache is cleared
	 */
	public static function remove(int $uri_id, int $type, int $cache_uid = 0)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		DBA::delete('post-collection', ['uri-id' => $uri_id, 'type' => $type]);

		if (!empty($cache_uid) && ($type == self::FEATURED)) {
			DI::cache()->delete(ActivityPub\Transmitter::CACHEKEY_FEATURED . $cache_uid);
		}
	}

	/**
	 * Fetch collections for a given contact
	 *
	 * @return array
	 */
	public static function selectToArrayForContact(int $cid, int $type = self::FEATURED, array $fields = [])
	{
		return DBA::selectToArray('collection-view', $fields, ['cid' => $cid, 'private' => [Item::PUBLIC, Item::UNLISTED], 'deleted' => false, 'type' => $type]);
	}
}
