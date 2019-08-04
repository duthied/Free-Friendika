<?php

namespace Friendica\Core\Cache;

/**
 * Abstract class for common used functions
 *
 * Class AbstractCache
 *
 * @package Friendica\Core\Cache
 */
abstract class AbstractCache implements ICache
{
	/**
	 * @var string The hostname
	 */
	private $hostName;

	public function __construct(string $hostName)
	{
		$this->hostName = $hostName;
	}

	/**
	 * Returns the prefix (to avoid namespace conflicts)
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function getPrefix()
	{
		// We fetch with the hostname as key to avoid problems with other applications
		return $this->hostName;
	}

	/**
	 * @param string $key The original key
	 * @return string        The cache key used for the cache
	 * @throws \Exception
	 */
	protected function getCacheKey($key)
	{
		return $this->getPrefix() . ":" . $key;
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
				$value = preg_replace('/^' . $this->hostName . ':/', '', $value);
			});

			sort($keys);

			return $keys;
		}
	}

	/**
	 * Filters the keys of an array with a given prefix
	 * Returns the filtered keys as an new array
	 *
	 * @param array $array The array, which should get filtered
	 * @param string|null $prefix The prefix (if null, all keys will get returned)
	 *
	 * @return array The filtered array with just the keys
	 */
	protected function filterArrayKeysByPrefix($array, $prefix = null)
	{
		if (empty($prefix)) {
			return array_keys($array);
		} else {
			$result = [];

			foreach (array_keys($array) as $key) {
				if (strpos($key, $prefix) === 0) {
					array_push($result, $key);
				}
			}

			return $result;
		}
	}
}
