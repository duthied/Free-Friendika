<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\KeyValueStorage\Capability;

use Friendica\Core\KeyValueStorage\Exceptions\KeyValueStoragePersistenceException;

/**
 * Interface for Friendica specific Key-Value pair storage
 */
interface IManageKeyValuePairs extends \ArrayAccess
{
	/**
	 * Get a particular value from the KeyValue Storage
	 *
	 * @param string  $key           The key to query
	 *
	 * @return mixed Stored value or null if it does not exist
	 *
	 * @throws KeyValueStoragePersistenceException In case the persistence layer throws errors
	 *
	 */
	public function get(string $key);

	/**
	 * Sets a value for a given key
	 *
	 * Note: Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $key    The configuration key to set
	 * @param mixed  $value  The value to store
	 *
	 * @throws KeyValueStoragePersistenceException In case the persistence layer throws errors
	 */
	public function set(string $key, $value): void;

	/**
	 * Deletes the given key.
	 *
	 * @param string $key    The configuration key to delete
	 *
	 * @throws KeyValueStoragePersistenceException In case the persistence layer throws errors
	 *
	 */
	public function delete(string $key): void;
}
