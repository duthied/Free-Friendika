<?php

/**
 * @file src/Core/Lock.php
 * @brief Functions for preventing parallel execution of functions
 */

namespace Friendica\Core;

use Friendica\BaseObject;
use Friendica\Core\Cache\Cache;
use Friendica\Core\Lock\ILock;

/**
 * This class contain Functions for preventing parallel execution of functions
 */
class Lock extends BaseObject
{
	/**
	 * @brief Acquires a lock for a given name
	 *
	 * @param string  $key     Name of the lock
	 * @param integer $timeout Seconds until we give up
	 * @param integer $ttl     The Lock lifespan, must be one of the Cache constants
	 *
	 * @return boolean Was the lock successful?
	 * @throws \Exception
	 */
	public static function acquire($key, $timeout = 120, $ttl = Cache::FIVE_MINUTES)
	{
		return self::getClass(ILock::class)->acquireLock($key, $timeout, $ttl);
	}

	/**
	 * @brief Releases a lock if it was set by us
	 *
	 * @param string $key      Name of the lock
	 * @param bool   $override Overrides the lock to get releases
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function release($key, $override = false)
	{
		return self::getClass(ILock::class)->releaseLock($key, $override);
	}

	/**
	 * @brief Releases all lock that were set by us
	 * @return void
	 * @throws \Exception
	 */
	public static function releaseAll()
	{
		self::getClass(ILock::class)->releaseAll();
	}
}
