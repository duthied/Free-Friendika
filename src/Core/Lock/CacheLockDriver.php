<?php

namespace Friendica\Core\Lock;

use Friendica\Core\Cache\IMemoryCacheDriver;

class CacheLockDriver extends AbstractLockDriver
{
	/**
	 * @var \Friendica\Core\Cache\ICacheDriver;
	 */
	private $cache;

	/**
	 * CacheLockDriver constructor.
	 *
	 * @param IMemoryCacheDriver $cache The CacheDriver for this type of lock
	 */
	public function __construct(IMemoryCacheDriver $cache)
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

		$cachekey = self::getCacheKey($key);

		do {
			$lock = $this->cache->get($cachekey);
			// When we do want to lock something that was already locked by us.
			if ((int)$lock == getmypid()) {
				$got_lock = true;
			}

			// When we do want to lock something new
			if (is_null($lock)) {
				// At first initialize it with "0"
				$this->cache->add($cachekey, 0);
				// Now the value has to be "0" because otherwise the key was used by another process meanwhile
				if ($this->cache->compareSet($cachekey, 0, getmypid(), 300)) {
					$got_lock = true;
					$this->markAcquire($key);
				}
			}

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
	 */
	public function releaseLock($key)
	{
		$cachekey = self::getCacheKey($key);

		$this->cache->compareDelete($cachekey, getmypid());
		$this->markRelease($key);
	}

	/**
	 * @brief Checks, if a key is currently locked to a process
	 *
	 * @param string $key The name of the lock
	 * @return bool
	 */
	public function isLocked($key)
	{
		$cachekey = self::getCacheKey($key);
		$lock = $this->cache->get($cachekey);
		return isset($lock) && ($lock !== false);
	}

	/**
	 * @param string $key	The original key
	 * @return string		The cache key used for the cache
	 */
	private static function getCacheKey($key) {
		return self::getApp()->get_hostname() . ";lock:" . $key;
	}
}
