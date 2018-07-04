<?php

namespace Friendica\Core\Lock;

class SemaphoreLockDriver extends AbstractLockDriver
{
	private static $semaphore = [];

	public function __construct()
	{
		if (!function_exists('sem_get')) {
			throw new \Exception('Semaphore lock not supported');
		}
	}

	/**
	 * @brief Creates a semaphore key
	 *
	 * @param string $key Name of the lock
	 *
	 * @return integer the semaphore key
	 */
	private static function semaphoreKey($key)
	{
		$temp = get_temppath();

		$file = $temp.'/'.$key.'.sem';

		if (!file_exists($file)) {
			file_put_contents($file, $key);
		}

		return ftok($file, 'f');
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
		self::$semaphore[$key] = sem_get(self::semaphoreKey($key));
		if (self::$semaphore[$key]) {
			if (sem_acquire(self::$semaphore[$key], ($timeout == 0))) {
				$this->markAcquire($key);
				return true;
			}
		}

		return false;
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
		if (empty(self::$semaphore[$key])) {
			return false;
		} else {
			$success = @sem_release(self::$semaphore[$key]);
			unset(self::$semaphore[$key]);
			$this->markRelease($key);
			return $success;
		}
	}

	/**
	 * @brief Checks, if a key is currently locked to a process
	 *
	 * @param string $key The name of the lock
	 * @return bool
	 */
	public function isLocked($key)
	{
		return @sem_get(self::$semaphore[$key]) !== false;
	}
}
