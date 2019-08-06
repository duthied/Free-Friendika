<?php

namespace Friendica\Core\Lock;

/**
 * Class AbstractLock
 *
 * @package Friendica\Core\Lock
 *
 * Basic class for Locking with common functions (local acquired locks, releaseAll, ..)
 */
abstract class Lock implements ILock
{
	/**
	 * @var array The local acquired locks
	 */
	protected $acquiredLocks = [];

	/**
	 * Check if we've locally acquired a lock
	 *
	 * @param string key The Name of the lock
	 *
	 * @return bool      Returns true if the lock is set
	 */
	protected function hasAcquiredLock($key)
	{
		return isset($this->acquireLock[$key]) && $this->acquiredLocks[$key] === true;
	}

	/**
	 * Mark a locally acquired lock
	 *
	 * @param string $key The Name of the lock
	 */
	protected function markAcquire($key)
	{
		$this->acquiredLocks[$key] = true;
	}

	/**
	 * Mark a release of a locally acquired lock
	 *
	 * @param string $key The Name of the lock
	 */
	protected function markRelease($key)
	{
		unset($this->acquiredLocks[$key]);
	}

	/**
	 * Releases all lock that were set by us
	 *
	 * @return boolean Was the unlock of all locks successful?
	 */
	public function releaseAll()
	{
		$return = true;

		foreach ($this->acquiredLocks as $acquiredLock => $hasLock) {
			if (!$this->releaseLock($acquiredLock)) {
				$return = false;
			}
		}

		return $return;
	}
}
