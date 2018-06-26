<?php

namespace Friendica\Core\Lock;

use Friendica\Core\Cache;
use dba;

class MemcacheLockDriver implements ILockDriver
{
	/**
	 *
	 * @brief Sets a lock for a given name
	 *
	 * @param string $key The Name of the lock
	 * @param integer $timeout Seconds until we give up
	 *
	 * @return boolean Was the lock successful?
	 */
	public function acquireLock($key, $timeout = 120)
	{
		$got_lock = false;
		$start = time();

		$cachekey = get_app()->get_hostname() . ";lock:" . $key;

		do {
			// We only lock to be sure that nothing happens at exactly the same time
			dba::lock('locks');
			$lock = Cache::get($cachekey);

			if (!is_bool($lock)) {
				$pid = (int)$lock;

				// When the process id isn't used anymore, we can safely claim the lock for us.
				// Or we do want to lock something that was already locked by us.
				if (!posix_kill($pid, 0) || ($pid == getmypid())) {
					$lock = false;
				}
			}
			if (is_bool($lock)) {
				Cache::set($cachekey, getmypid(), 300);
				$got_lock = true;
			}

			dba::unlock();

			if (!$got_lock && ($timeout > 0)) {
				usleep(rand(10000, 200000));
			}
		} while (!$got_lock && ((time() - $start) < $timeout));

		return $got_lock;
	}

	/**
	 * @brief Removes a lock if it was set by us
	 *
	 * @param string $key Name of the lock
	 *
	 * @return mixed
	 */
	public function releaseLock($key)
	{
		$cachekey = get_app()->get_hostname() . ";lock:" . $key;
		$lock = Cache::get($cachekey);

		if (!is_bool($lock)) {
			if ((int)$lock == getmypid()) {
				Cache::delete($cachekey);
			}
		}

		return;
	}

	/**
	 * @brief Removes all lock that were set by us
	 *
	 * @return void
	 */
	public function releaseAll()
	{
		// We cannot delete all cache entries, but this doesn't matter with memcache
		return;
	}
}