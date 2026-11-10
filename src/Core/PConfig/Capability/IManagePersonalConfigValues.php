<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\PConfig\Capability;

use Friendica\Core\PConfig\ValueObject;

/**
 * Interface for accessing user specific configurations
 */
interface IManagePersonalConfigValues
{
	/**
	 * Loads all configuration values of a user's config family into a cached storage.
	 *
	 * All configuration values of the given user are stored with the $uid in the cache
	 *
	 * @param int    $uid The user_id
	 * @param string $cat The category of the configuration value
	 *
	 * @return array The loaded config array
	 */
	public function load(int $uid, string $cat = 'config'): array;

	/**
	 * Get a particular user's config variable given the category name
	 * ($cat) and a key.
	 *
	 * Get a particular user's config value from the given category ($cat)
	 * and the $key with the $uid from a cached storage either from the database
	 * or from the configCache
	 *
	 * @param int     $uid           The user_id
	 * @param string  $cat           The category of the configuration value
	 * @param string  $key           The configuration key to query
	 * @param mixed   $default_value Deprecated, use `PConfig->get($uid, $cat, $key, null, $refresh) ?? $default_value` instead
	 * @param boolean $refresh       optional, If true the config is loaded from the db and not from the cache (default: false)
	 *
	 * @return mixed Stored value or null if it does not exist
	 *
	 */
	public function get(int $uid, string $cat, string $key, $default_value = null, bool $refresh = false);

	/**
	 * Sets a configuration value for a user
	 *
	 * Stores a config value ($value) in the category ($family) under the key ($key)
	 * for the user_id $uid.
	 *
	 * @note  Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param int    $uid   The user_id
	 * @param string $cat   The category of the configuration value
	 * @param string $key   The configuration key to set
	 * @param mixed  $value The value to store
	 *
	 * @return bool Operation success
	 */
	public function set(int $uid, string $cat, string $key, $value): bool;

	/**
	 * Deletes the given key from the users configuration.
	 *
	 * Removes the configured value from the stored cache and removes it from the database with the given $uid.
	 *
	 * @param int    $uid The user_id
	 * @param string $cat The category of the configuration value
	 * @param string $key The configuration key to delete
	 *
	 * @return bool
	 */
	public function delete(int $uid, string $cat, string $key): bool;


	/**
	 * Returns the Config Cache
	 *
	 * @return ValueObject\Cache
	 */
	public function getCache(): ValueObject\Cache;
}
