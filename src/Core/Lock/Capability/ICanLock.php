<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Lock\Capability;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Lock\Exception\LockPersistenceException;

/**
 * Lock Interface
 */
interface ICanLock
{
	/**
	 * Checks, if a key is currently locked to a or my process
	 *
	 * @param string $key The name of the lock
	 */
	public function isLocked(string $key): bool;

	/**
	 *
	 * Acquires a lock for a given name
	 *
	 * @param string  $key     The Name of the lock
	 * @param integer $timeout Seconds until we give up
	 * @param integer $ttl     Seconds The lock lifespan, must be one of the Cache constants
	 *
	 * @throws LockPersistenceException In case the underlying persistence throws errors
	 */
	public function acquire(string $key, int $timeout = 120, int $ttl = Duration::FIVE_MINUTES): bool;

	/**
	 * Releases a lock if it was set by us
	 *
	 * @param string $key      The Name of the lock
	 * @param bool   $override Overrides the lock to get released
	 *
	 * @return bool Was the unlock successful?
	 *
	 * @throws LockPersistenceException In case the underlying persistence throws errors
	 */
	public function release(string $key, bool $override = false): bool;

	/**
	 * Releases all lock that were set by us
	 *
	 * @param bool $override Override to release all locks
	 *
	 * @return bool Was the unlock of all locks successful?
	 *
	 * @throws LockPersistenceException In case the underlying persistence throws errors
	 */
	public function releaseAll(bool $override = false): bool;

	/**
	 * Returns the name of the current lock
	 */
	public function getName(): string;

	/**
	 * Lists all locks
	 *
	 * @param string $prefix optional a prefix to search
	 *
	 * @return string[] Empty if it isn't supported by the cache driver
	 *
	 * @throws LockPersistenceException In case the underlying persistence throws errors
	 */
	public function getLocks(string $prefix = ''): array;
}
