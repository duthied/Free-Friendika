<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Core\Cache;

use Friendica\Core\BaseCache;

/**
 * Implementation of the IMemoryCache mainly for testing purpose
 */
class ArrayCache extends BaseCache implements IMemoryCache
{
	use TraitCompareDelete;

	/** @var array Array with the cached data */
	protected $cachedData = array();

	/**
	 * (@inheritdoc)
	 */
	public function getAllKeys($prefix = null)
	{
		return $this->filterArrayKeysByPrefix(array_keys($this->cachedData), $prefix);
	}

	/**
	 * (@inheritdoc)
	 */
	public function get($key)
	{
		if (isset($this->cachedData[$key])) {
			return $this->cachedData[$key];
		}
		return null;
	}

	/**
	 * (@inheritdoc)
	 */
	public function set($key, $value, $ttl = Duration::FIVE_MINUTES)
	{
		$this->cachedData[$key] = $value;
		return true;
	}

	/**
	 * (@inheritdoc)
	 */
	public function delete($key)
	{
		unset($this->cachedData[$key]);
		return true;
	}

	/**
	 * (@inheritdoc)
	 */
	public function clear($outdated = true)
	{
		// Array doesn't support TTL so just don't delete something
		if ($outdated) {
			return true;
		}

		$this->cachedData = [];
		return true;
	}

	/**
	 * (@inheritdoc)
	 */
	public function add($key, $value, $ttl = Duration::FIVE_MINUTES)
	{
		if (isset($this->cachedData[$key])) {
			return false;
		} else {
			return $this->set($key, $value, $ttl);
		}
	}

	/**
	 * (@inheritdoc)
	 */
	public function compareSet($key, $oldValue, $newValue, $ttl = Duration::FIVE_MINUTES)
	{
		if ($this->get($key) === $oldValue) {
			return $this->set($key, $newValue);
		} else {
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return Type::ARRAY;
	}
}
