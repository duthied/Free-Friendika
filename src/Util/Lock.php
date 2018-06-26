<?php
/**
 * @file src/Core/Lock.php
 */
namespace Friendica\Util;

/**
 * @file src/Core/Lock.php
 * @brief Functions for preventing parallel execution of functions
 */

use Friendica\Core\Config;
use Friendica\Util\Lock;

require_once 'include/dba.php';

/**
 * @brief This class contain Functions for preventing parallel execution of functions
 */
class Lock
{
	/**
	 * @var Lock\ILockDriver;
	 */
	static $driver = null;

	public static function init()
	{
		switch(Config::get('system', 'lock_driver', 'default')) {
			case 'memcache':
				self::$driver = new Lock\MemcacheLockDriver();
				break;
			case 'database':
				self::$driver = new Lock\DatabaseLockDriver();
				break;
			case 'semaphore':
				self::$driver = new Lock\SemaphoreLockDriver();
				break;
			default:
				// Determine what's the best/fastest locking driver (default behavior in Friendica)
				if (function_exists('sem_get') && version_compare(PHP_VERSION, '5.6.1', '>=')) {
					self::$driver = new Lock\SemaphoreLockDriver();
				} elseif (Config::get('system', 'cache_driver', 'database') == 'memcache') {
					self::$driver = new Lock\MemcacheLockDriver();
				} else {
					self::$driver = new Lock\DatabaseLockDriver();
				}
		}
	}

	/**
	 * Returns the current cache driver
	 *
	 * @return Lock\ILockDriver;
	 */
	private static function getDriver()
	{
		if (self::$driver === null) {
			self::init();
		}

		return self::$driver;
	}

	/**
	 * @brief Acquires a lock for a given name
	 *
	 * @param string  $key Name of the lock
	 * @param integer $timeout Seconds until we give up
	 *
	 * @return boolean Was the lock successful?
	 */
	public static function acquireLock($key, $timeout = 120)
	{
		return self::getDriver()->acquireLock($key, $timeout);
	}

	/**
	 * @brief Releases a lock if it was set by us
	 *
	 * @param string $key Name of the lock
	 * @return mixed
	 */
	public static function releaseLock($key)
	{
		return self::getDriver()->releaseLock($key);
	}

	/**
	 * @brief Releases all lock that were set by us
	 * @return void
	 */
	public static function releaseAll()
	{
		self::getDriver()->releaseAll();
	}

	public static function isLocked($key)
	{

	}
}
