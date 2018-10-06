<?php

namespace Friendica\Core\Cache;
use Friendica\BaseObject;


/**
 * Abstract class for common used functions
 *
 * Class AbstractCacheDriver
 *
 * @package Friendica\Core\Cache
 */
abstract class AbstractCacheDriver extends BaseObject
{
	/**
	 * @param string $key	The original key
	 * @return string		The cache key used for the cache
	 */
	protected function getCacheKey($key)
	{
		// We fetch with the hostname as key to avoid problems with other applications
		return self::getApp()->get_hostname() . ":" . $key;
	}

	/**
	 * @param array $keys   A list of cached keys
	 * @return array        A list of original keys
	 */
	protected function getOriginalKeys($keys)
	{
		if (empty($keys)) {
			return [];
		} else {
			// Keys are prefixed with the node hostname, let's remove it
			array_walk($keys, function (&$value) {
				$value = preg_replace('/^' . self::getApp()->get_hostname() . ':/', '', $value);
			});

			sort($keys);

			return $keys;
		}
	}

	/**
	 * Filters a list for a given prefix
	 *
	 * @param array $list the list
	 * @param string|null $prefix the prefix
	 *
	 * @return array the filtered list
	 */
	protected function filterPrefix($list, $prefix = null)
	{
		if (empty($prefix)) {
			return array_keys($list);
		} else {
			$result = [];

			foreach (array_keys($list) as $key) {
				if (strpos($key, $prefix) === 0) {
					array_push($result, $key);
				}
			}

			return $result;
		}

	}
}
