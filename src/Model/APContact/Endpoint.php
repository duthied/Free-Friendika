<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model\APContact;

use Friendica\Database\DBA;

class Endpoint
{
	// Mobilizon Endpoints
	public const DISCUSSIONS = 10;
	public const EVENTS      = 11;
	public const MEMBERS     = 12;
	public const POSTS       = 13;
	public const RESOURCES   = 14;
	public const TODOS       = 15;

	// Peertube Endpoints
	public const PLAYLISTS = 20;

	// Mastodon Endpoints
	public const DEVICES = 30;

	public const ENDPOINT_NAMES = [
		self::PLAYLISTS   => 'pt:playlists',
		self::DISCUSSIONS => 'mobilizon:discussions',
		self::EVENTS      => 'mobilizon:events',
		self::MEMBERS     => 'mobilizon:members',
		self::POSTS       => 'mobilizon:posts',
		self::RESOURCES   => 'mobilizon:resources',
		self::TODOS       => 'mobilizon:todos',
		self::DEVICES     => 'toot:devices',
	];

	/**
	 * Update an apcontact endpoint
	 *
	 * @param int    $owner_uri_id
	 * @param int    $type
	 * @param string $url
	 * @return bool
	 */
	public static function update(int $owner_uri_id, int $type, string $url)
	{
		if (empty($url) || empty($owner_uri_id)) {
			return false;
		}

		$fields = ['owner-uri-id' => $owner_uri_id, 'type' => $type];

		return DBA::update('endpoint', $fields, ['url' => $url], true);
	}
}
