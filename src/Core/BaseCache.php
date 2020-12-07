<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Core;

use Friendica\Core\Cache\ICache;

/**
 * Abstract class for common used functions
 */
abstract class BaseCache implements ICache
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
	 * @param array $keys The keys, which should get filtered
	 * @param string|null $prefix The prefix (if null, all keys will get returned)
	 *
	 * @return array The filtered array with just the keys
	 */
	protected function filterArrayKeysByPrefix(array $keys, string $prefix = null)
	{
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
}
