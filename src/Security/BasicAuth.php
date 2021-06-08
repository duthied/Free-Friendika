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

namespace Friendica\Security;

use Friendica\Database\DBA;
use Friendica\DI;

/**
 * Authentification via the basic auth method
 */
class BasicAuth
{
	/**
	 * @var bool|int
	 */
	protected static $current_user_id = 0;
	/**
	 * @var array
	 */
	protected static $current_token = [];

	/**
	 * Fetch a dummy application token
	 *
	 * @return array token
	 */
	public static function getCurrentApplicationToken()
	{
		if (empty(self::getCurrentUserID())) {
			return [];
		}

		if (!empty(self::$current_token)) {
			return self::$current_token;
		}

		self::$current_token = [
			'uid'        => self::$current_user_id,
			'id'         => 0,
			'name'       => api_source(),
			'website'    => '',
			'created_at' => DBA::NULL_DATETIME,
			'read'       => true,
			'write'      => true,
			'follow'     => true,
			'push'       => false];

		return self::$current_token;
	}

	/**
	 * Get current user id, returns 0 if $login is set to false and not logged in.
	 * When $login is true, the execution will stop when not logged in.
	 *
	 * @param bool $login Perform a login request if "true"
	 *
	 * @return int User ID
	 */
	public static function getCurrentUserID(bool $login = true)
	{
		if (empty(self::$current_user_id)) {
			api_login(DI::app(), $login);

			self::$current_user_id = api_user();
		}

		return (int)self::$current_user_id;
	}
}
