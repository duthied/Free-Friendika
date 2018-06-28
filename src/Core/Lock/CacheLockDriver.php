<?php

namespace Friendica\Core\Lock;

use Friendica\Core\Cache\ICacheDriver;

class CacheLockDriver extends AbstractLockDriver
{
	/**
	 * @var \Friendica\Core\Cache\ICacheDriver;
	 */
	private $cache;

	/**
	 * CacheLockDriver constructor.
	 *
	 * @param ICacheDriver $cache The CacheDriver for this type of lock
	 */
	public function __construct(ICacheDriver $cache)
	{
		$this->cache = $cache;
	}

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
			$lock = $this->cache->get($cachekey);

			if (!is_bool($lock)) {
				$pid = (int)$lock;

				// When the process id isn't used anymore, we can safely claim the lock for us.
				// Or we do want to lock something that was already locked by us.
				if (!posix_kill($pid, 0) || ($pid == getmypid())) {
					$lock = false;
				}
			}
			if (is_bool($lock)) {
				$this->cache->set($cachekey, getmypid(), 300);
				$got_lock = true;
			}

			if (!$got_lock && ($timeout > 0)) {
				usleep(rand(10000, 200000));
			}
		} while (!$got_lock && ((time() - $start) < $timeout));

		$this->markAcquire($key);

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
		$lock = $this->cache->get($cachekey);

		if (!is_bool($lock)) {
			if ((int)$lock == getmypid()) {
				$this->cache->delete($cachekey);
			}
		}

		$this->markRelease($key);

		return;
	}
}
