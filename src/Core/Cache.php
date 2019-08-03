<?php
/**
 * @file src/Core/Cache.php
 */
namespace Friendica\Core;

use Friendica\BaseObject;
use Friendica\Core\Cache\ICacheDriver;

/**
 * @brief Class for storing data for a short time
 */
class Cache extends BaseObject
{
	/** @deprecated Use ICacheDriver::MONTH */
	const MONTH        = ICacheDriver::MONTH;
	/** @deprecated Use ICacheDriver::WEEK */
	const WEEK         = 604800;
	/** @deprecated Use ICacheDriver::DAY */
	const DAY          = 86400;
	/** @deprecated Use ICacheDriver::HOUR */
	const HOUR         = 3600;
	/** @deprecated Use ICacheDriver::HALF_HOUR */
	const HALF_HOUR    = 1800;
	/** @deprecated Use ICacheDriver::QUARTER_HOUR */
	const QUARTER_HOUR = 900;
	/** @deprecated Use ICacheDriver::FIVE_MINUTES */
	const FIVE_MINUTES = 300;
	/** @deprecated Use ICacheDriver::MINUTE */
	const MINUTE       = 60;
	/** @deprecated Use ICacheDriver::INFINITE */
	const INFINITE     = 0;

	/**
	 * @brief Returns all the cache keys sorted alphabetically
	 *
	 * @param string $prefix Prefix of the keys (optional)
	 *
	 * @return array Empty if the driver doesn't support this feature
	 * @throws \Exception
	 */
	public static function getAllKeys($prefix = null)
	{
		return self::getClass(ICacheDriver::class)->getAllKeys($prefix);
	}

	/**
	 * @brief Fetch cached data according to the key
	 *
	 * @param string $key The key to the cached data
	 *
	 * @return mixed Cached $value or "null" if not found
	 * @throws \Exception
	 */
	public static function get($key)
	{
		return self::getClass(ICacheDriver::class)->get($key);
	}

	/**
	 * @brief Put data in the cache according to the key
	 *
	 * The input $value can have multiple formats.
	 *
	 * @param string  $key      The key to the cached data
	 * @param mixed   $value    The value that is about to be stored
	 * @param integer $duration The cache lifespan
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public static function set($key, $value, $duration = ICacheDriver::MONTH)
	{
		return self::getClass(ICacheDriver::class)->set($key, $value, $duration);
	}

	/**
	 * @brief Delete a value from the cache
	 *
	 * @param string $key The key to the cached data
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public static function delete($key)
	{
		return self::getClass(ICacheDriver::class)->delete($key);
	}

	/**
	 * @brief Remove outdated data from the cache
	 *
	 * @param boolean $outdated just remove outdated values
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public static function clear($outdated = true)
	{
		return self::getClass(ICacheDriver::class)->clear($outdated);
	}
}
