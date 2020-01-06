<?php
/**
 * @file src/Core/Cache.php
 */
namespace Friendica\Core;

use Friendica\Core\Cache\Cache as CacheClass;
use Friendica\DI;

/**
 * @brief Class for storing data for a short time
 */
class Cache
{
	/** @deprecated Use CacheClass::MONTH */
	const MONTH        = CacheClass::MONTH;
	/** @deprecated Use CacheClass::WEEK */
	const WEEK         = CacheClass::WEEK;
	/** @deprecated Use CacheClass::DAY */
	const DAY          = CacheClass::DAY;
	/** @deprecated Use CacheClass::HOUR */
	const HOUR         = CacheClass::HOUR;
	/** @deprecated Use CacheClass::HALF_HOUR */
	const HALF_HOUR    = CacheClass::HALF_HOUR;
	/** @deprecated Use CacheClass::QUARTER_HOUR */
	const QUARTER_HOUR = CacheClass::QUARTER_HOUR;
	/** @deprecated Use CacheClass::FIVE_MINUTES */
	const FIVE_MINUTES = CacheClass::FIVE_MINUTES;
	/** @deprecated Use CacheClass::MINUTE */
	const MINUTE       = CacheClass::MINUTE;
	/** @deprecated Use CacheClass::INFINITE */
	const INFINITE     = CacheClass::INFINITE;

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
		return DI::cache()->get($key);
	}
}
