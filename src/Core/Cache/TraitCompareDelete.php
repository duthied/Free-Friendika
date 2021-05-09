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

/**
 * Trait TraitCompareSetDelete
 *
 * This Trait is to compensate non native "exclusive" sets/deletes in caches
 */
trait TraitCompareDelete
{
	abstract public function get($key);

	abstract public function set($key, $value, $ttl = Duration::FIVE_MINUTES);

	abstract public function delete($key);

	abstract public function add($key, $value, $ttl = Duration::FIVE_MINUTES);

	/**
	 * NonNative - Compares if the old value is set and removes it
	 *
	 * @param string $key          The cache key
	 * @param mixed  $value        The old value we know and want to delete
	 * @return bool
	 */
	public function compareDelete($key, $value) {
		if ($this->add($key . "_lock", true)) {
			if ($this->get($key) === $value) {
				$this->delete($key);
				$this->delete($key . "_lock");
				return true;
			} else {
				$this->delete($key . "_lock");
				return false;
			}
		} else {
			return false;
		}
	}
}
