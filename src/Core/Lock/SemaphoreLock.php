<?php

namespace Friendica\Core\Lock;

use Friendica\Core\Cache;

class SemaphoreLock extends Lock
{
	private static $semaphore = [];

	public function __construct()
	{
		if (!function_exists('sem_get')) {
			throw new \Exception('Semaphore lock not supported');
		}
	}

	/**
	 * (@inheritdoc)
	 */
	private static function semaphoreKey($key)
	{
		$success = true;

		$temp = get_temppath();

		$file = $temp . '/' . $key . '.sem';

		if (!file_exists($file)) {
			$success = !empty(file_put_contents($file, $key));
		}

		return $success ? ftok($file, 'f') : false;
	}

	/**
	 * (@inheritdoc)
	 */
	public function acquireLock($key, $timeout = 120, $ttl = Cache\Cache::FIVE_MINUTES)
	{
		self::$semaphore[$key] = sem_get(self::semaphoreKey($key));
		if (!empty(self::$semaphore[$key])) {
			if ((bool)sem_acquire(self::$semaphore[$key], ($timeout === 0))) {
				$this->markAcquire($key);
				return true;
			}
		}

		return false;
	}

	/**
	 * (@inheritdoc)
	 *
	 * @param bool $override not necessary parameter for semaphore locks since the lock lives as long as the execution
	 *                       of the using function
	 */
	public function releaseLock($key, $override = false)
	{
		$success = false;

		if (!empty(self::$semaphore[$key])) {
			try {
				$success = @sem_release(self::$semaphore[$key]);
				unset(self::$semaphore[$key]);
				$this->markRelease($key);
			} catch (\Exception $exception) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * (@inheritdoc)
	 */
	public function isLocked($key)
	{
		return isset(self::$semaphore[$key]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return self::TYPE_SEMAPHORE;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLocks(string $prefix = '')
	{
		// We can just return our own semaphore keys, since we don't know
		// the state of other semaphores, even if the .sem files exists
		$keys = array_keys(self::$semaphore);

		if (empty($prefix)) {
			return $keys;
		} else {
			$result = [];

			foreach ($keys as $key) {
				if (strpos($key, $prefix) === 0) {
					array_push($result, $key);
				}
			}

			return $result;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function releaseAll($override = false)
	{
		// Semaphores are just alive during a run, so there is no need to release
		// You can just release your own locks
		return parent::releaseAll($override);
	}
}
