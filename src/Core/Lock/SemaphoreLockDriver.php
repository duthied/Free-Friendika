<?php

namespace Friendica\Core\Lock;

class SemaphoreLockDriver extends AbstractLockDriver
{
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
		$this->acquiredLocks[$key] = sem_get(self::semaphoreKey($key));
		if ($this->acquiredLocks[$key]) {
			return sem_acquire($this->acquiredLocks[$key], ($timeout == 0));
		}
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
		if (empty($this->acquiredLocks[$key])) {
			return false;
		} else {
			$success = @sem_release($this->acquiredLocks[$key]);
			unset($this->acquiredLocks[$key]);
			return $success;
		}
	}
}
