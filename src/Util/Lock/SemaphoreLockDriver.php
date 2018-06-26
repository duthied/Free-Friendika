<?php

namespace Friendica\Util\Lock;

class SemaphoreLockDriver implements ILockDriver
{
	/**
	 * @var array stored semaphores
	 */
	private static $semaphore = [];

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
		// The second parameter for "sem_acquire" doesn't exist before 5.6.1
		if (function_exists('sem_get') && version_compare(PHP_VERSION, '5.6.1', '>=')) {
			self::$semaphore[$key] = sem_get(self::semaphoreKey($key));
			if (self::$semaphore[$key]) {
				return sem_acquire(self::$semaphore[$key], ($timeout == 0));
			}
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
		if (function_exists('sem_get') && version_compare(PHP_VERSION, '5.6.1', '>=')) {
			if (empty(self::$semaphore[$key])) {
				return false;
			} else {
				$success = @sem_release(self::$semaphore[$key]);
				unset(self::$semaphore[$key]);
				return $success;
			}
		}
	}

	/**
	 * @brief Removes all lock that were set by us
	 *
	 * @return void
	 */
	public function releaseAll()
	{
		// not needed/supported
		return;
	}
}