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

namespace Friendica\Core\Session;

/**
 * Contains all global supported Session methods
 */
interface ISession
{
	/**
	 * Start the current session
	 *
	 * @return self The own Session instance
	 */
	public function start();

	/**
	 * Checks if the key exists in this session
	 *
	 * @param string $name
	 *
	 * @return boolean True, if it exists
	 */
	public function exists(string $name);

	/**
	 * Retrieves a key from the session super global or the defaults if the key is missing or the value is falsy.
	 *
	 * Handle the case where session_start() hasn't been called and the super global isn't available.
	 *
	 * @param string $name
	 * @param mixed  $defaults
	 *
	 * @return mixed
	 */
	public function get(string $name, $defaults = null);

	/**
	 * Sets a single session variable.
	 * Overrides value of existing key.
	 *
	 * @param string $name
	 * @param mixed  $value
	 */
	public function set(string $name, $value);

	/**
	 * Sets multiple session variables.
	 * Overrides values for existing keys.
	 *
	 * @param array $values
	 */
	public function setMultiple(array $values);

	/**
	 * Removes a session variable.
	 * Ignores missing keys.
	 *
	 * @param string $name
	 */
	public function remove(string $name);

	/**
	 * Clears the current session array
	 */
	public function clear();
}
