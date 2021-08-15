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

 /**
  * @see https://github.com/web-push-libs/web-push-php
  * Possibly we should simply use this.
  */
namespace Friendica\Model;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\Crypto;

class Subscription
{
	/**
	 * Select a subscription record exists
	 *
	 * @param int   $applicationid
	 * @param int   $uid
	 * @param array $fields
	 *
	 * @return bool Does it exist?
	 */
	public static function select(int $applicationid, int $uid, array $fields = [])
	{
		return DBA::selectFirst('subscription', $fields, ['application-id' => $applicationid, 'uid' => $uid]);
	}

	/**
	 * Check if a subscription record exists
	 *
	 * @param int   $applicationid
	 * @param int   $uid
	 *
	 * @return bool Does it exist?
	 */
	public static function exists(int $applicationid, int $uid)
	{
		return DBA::exists('subscription', ['application-id' => $applicationid, 'uid' => $uid]);
	}

	/**
	 * Update a subscription record
	 *
	 * @param int   $applicationid
	 * @param int   $uid
	 * @param array $fields subscription fields
	 *
	 * @return bool result of update
	 */
	public static function update(int $applicationid, int $uid, array $fields)
	{
		return DBA::update('subscription', $fields, ['application-id' => $applicationid, 'uid' => $uid]);
	}

	/**
	 * Insert or replace a subscription record
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
	 *
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
	 *
	 * @return string
	 */
	public static function getVapidKey(): string
	{
		$keypair = DI::config()->get('system', 'ec_keypair');
		if (empty($keypair)) {
			$keypair = Crypto::newECKeypair();
			DI::config()->set('system', 'ec_keypair', $keypair);
		}
		return $keypair['vapid-public'];
	}
}
