<?php

namespace Friendica\Core\Cache;

use Friendica\Core\Cache;

/**
 * Cache Driver Interface
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
interface ICacheDriver
{
	/**
	 * @brief Fetches cached data according to the key
	 *
	 * @param string $key The key to the cached data
	 *
	 * @return mixed Cached $value or "null" if not found
	 */
	public function get($key);

	/**
	 * @brief Stores data in the cache identified by the key. The input $value can have multiple formats.
	 *
	 * @param string  $key      The cache key
	 * @param mixed   $value    The value to store
	 * @param integer $ttl 		 The cache lifespan, must be one of the Cache constants
	 *
	 * @return bool
	 */
	public function set($key, $value, $ttl = Cache::FIVE_MINUTES);

	/**
	 * @brief Delete a key from the cache
	 *
	 * @param string $key      The cache key
	 *
	 * @return bool
	 */
	public function delete($key);

	/**
	 * @brief Remove outdated data from the cache
	 *
	 * @return bool
	 */
	public function clear();
}
