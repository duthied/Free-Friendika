<?php

namespace Friendica\Core\Lock;

use Friendica\Core\Cache;

/**
 * Lock Interface
 *
 * @author Philipp Holzer <admin@philipp.info>
 */
interface ILock
{
	/**
	 * Checks, if a key is currently locked to a or my process
	 *
	 * @param string $key The name of the lock
	 *
	 * @return bool
	 */
	public function isLocked($key);

	/**
	 *
	 * Acquires a lock for a given name
	 *
	 * @param string  $key     The Name of the lock
	 * @param integer $timeout Seconds until we give up
	 * @param integer $ttl     Seconds The lock lifespan, must be one of the Cache constants
	 *
	 * @return boolean Was the lock successful?
	 */
	public function acquireLock($key, $timeout = 120, $ttl = Cache\Cache::FIVE_MINUTES);

	/**
	 * Releases a lock if it was set by us
	 *
	 * @param string $key      The Name of the lock
	 * @param bool   $override Overrides the lock to get released
	 *
	 * @return boolean Was the unlock successful?
	 */
	public function releaseLock($key, $override = false);

	/**
	 * Releases all lock that were set by us
	 *
	 * @param bool $override Override to release all locks
	 *
	 * @return boolean Was the unlock of all locks successful?
	 */
	public function releaseAll($override = false);

	/**
	 * Returns the name of the current lock
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Lists all locks
	 *
	 * @param string prefix optional a prefix to search
	 *
	 * @return array Empty if it isn't supported by the cache driver
	 */
	public function getLocks(string $prefix = '');
}
