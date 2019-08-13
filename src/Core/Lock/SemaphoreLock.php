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
		$file = self::keyToFile($key);

		if (!file_exists($file)) {
			file_put_contents($file, $key);
		}

		return ftok($file, 'f');
	}

	/**
	 * Returns the full path to the semaphore file
	 *
	 * @param string $key The key of the semaphore
	 *
	 * @return string The full path
	 */
	private static function keyToFile($key)
	{
		$temp = get_temppath();

		return $temp . '/' . $key . '.sem';
	}

	/**
	 * (@inheritdoc)
	 */
	public function acquireLock($key, $timeout = 120, $ttl = Cache\Cache::FIVE_MINUTES)
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
	 * (@inheritdoc)
	 */
	public function releaseLock($key, $override = false)
	{
		$success = false;

		if (!empty(self::$semaphore[$key])) {
			try {
				$success = @sem_release(self::$semaphore[$key]) &&
				           unlink(self::keyToFile($key));
				unset(self::$semaphore[$key]);
				$this->markRelease($key);
			} catch (\Exception $exception) {
				$success = false;
			}
		} else if ($override) {
			if ($this->acquireLock($key)) {
				$success = $this->releaseLock($key, true);
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
		$temp = get_temppath();
		$locks = [];
		foreach (glob(sprintf('%s/%s*.sem', $temp, $prefix)) as $lock) {
			$lock = pathinfo($lock, PATHINFO_FILENAME);
			if(sem_get(self::semaphoreKey($lock))) {
				$locks[] = $lock;
			}
		}

		return $locks;
	}

	/**
	 * {@inheritDoc}
	 */
	public function releaseAll($override = false)
	{
		$success = parent::releaseAll($override);

		$temp = get_temppath();
		foreach (glob(sprintf('%s/*.sem', $temp)) as $lock) {
			$lock = pathinfo($lock, PATHINFO_FILENAME);
			if (!$this->releaseLock($lock, true)) {
				$success = false;
			}
		}

		return $success;
	}
}
