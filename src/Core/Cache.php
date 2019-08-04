<?php
/**
 * @file src/Core/Cache.php
 */
namespace Friendica\Core;

use Friendica\BaseObject;
use Friendica\Core\Cache\ICache;

/**
 * @brief Class for storing data for a short time
 */
class Cache extends BaseObject
{
	/** @deprecated Use ICache::MONTH */
	const MONTH        = ICache::MONTH;
	/** @deprecated Use ICache::WEEK */
	const WEEK         = ICache::WEEK;
	/** @deprecated Use ICache::DAY */
	const DAY          = ICache::DAY;
	/** @deprecated Use ICache::HOUR */
	const HOUR         = ICache::HOUR;
	/** @deprecated Use ICache::HALF_HOUR */
	const HALF_HOUR    = ICache::HALF_HOUR;
	/** @deprecated Use ICache::QUARTER_HOUR */
	const QUARTER_HOUR = ICache::QUARTER_HOUR;
	/** @deprecated Use ICache::FIVE_MINUTES */
	const FIVE_MINUTES = ICache::FIVE_MINUTES;
	/** @deprecated Use ICache::MINUTE */
	const MINUTE       = ICache::MINUTE;
	/** @deprecated Use ICache::INFINITE */
	const INFINITE     = ICache::INFINITE;

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
		return self::getClass(ICache::class)->getAllKeys($prefix);
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
		return self::getClass(ICache::class)->get($key);
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
	public static function set($key, $value, $duration = ICache::MONTH)
	{
		return self::getClass(ICache::class)->set($key, $value, $duration);
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
		return self::getClass(ICache::class)->delete($key);
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
		return self::getClass(ICache::class)->clear($outdated);
	}
}
