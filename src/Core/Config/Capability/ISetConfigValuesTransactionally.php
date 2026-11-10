<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Config\Capability;

use Friendica\Core\Config\Exception\ConfigPersistenceException;

/**
 * Interface for transactional saving of config values
 * It buffers every set/delete until "save()" is called
 */
interface ISetConfigValuesTransactionally
{
	/**
	 * Sets a configuration value for system config
	 *
	 * Stores a config value ($value) in the category ($cat) under the key ($key)
	 *
	 * Note: Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $cat The category of the configuration value
	 * @param string $key    The configuration key to set
	 * @param mixed  $value  The value to store
	 *
	 * @return static the current instance
	 */
	public function set(string $cat, string $key, $value): self;

	/**
	 * Deletes the given key from the system configuration.
	 *
	 * @param string $cat The category of the configuration value
	 * @param string $key The configuration key to delete
	 *
	 * @return static the current instance
	 *
	 */
	public function delete(string $cat, string $key): self;

	/**
	 * Commits the changes of the current transaction
	 *
	 * @throws ConfigPersistenceException In case the persistence layer throws errors
	 */
	public function commit(): void;
}
