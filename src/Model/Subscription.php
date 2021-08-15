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

namespace Friendica\Model;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\Crypto;

class Subscription
{
	/**
	 * Insert an Subscription record
	 *
	 * @param array $fields subscription fields
	 *
	 * @return bool result of replace
	 */
	public static function replace(array $fields)
	{
		return DBA::replace('subscription', $fields);
	}

	/**
	 * Delete a subscription record
	 * @param int $applicationid
	 * @param int $uid
	 * @return bool
	 */
	public static function delete(int $applicationid, int $uid)
	{
		return DBA::delete('subscription', ['application-id' => $applicationid, 'uid' => $uid]);
	}

	/**
	 * Fetch a VAPID key
	 * @return string
	 */
	public static function getVapidKey(): string
	{
		$keypair = DI::config()->get('system', 'ec_keypair');
		if (empty($keypair)) {
			$keypair = Crypto::newECKeypair();
			DI::config()->set('system', 'ec_keypair', $keypair);
		}
		return $keypair['vapid'];
	}
}
