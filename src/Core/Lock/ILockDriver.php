<?php

namespace Friendica\Core\Lock;

/**
 * Lock Driver Interface
 *
 * @author Philipp Holzer <admin@philipp.info>
 */
interface ILockDriver
{
	/**
	 *
	 * @brief Acquires a lock for a given name
	 *
	 * @param string  $key      The Name of the lock
	 * @param integer $timeout  Seconds until we give up
	 *
	 * @return boolean Was the lock successful?
	 */
	public function acquireLock($key, $timeout = 120);

	/**
	 * @brief Releases a lock if it was set by us
	 *
	 * @param string $key Name of the lock
	 *
	 * @return mixed
	 */
	public function releaseLock($key);

	/**
	 * @brief Releases all lock that were set by us
	 *
	 * @return void
	 */
	public function releaseAll();
}