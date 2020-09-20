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

namespace Friendica\Worker;

use Friendica\Database\DBA;
use Friendica\Model\Photo;
use Friendica\Model\User;

/**
 * Expire and remove user entries
 */
class ExpireAndRemoveUsers
{
	public static function execute()
	{
		// expire any expired regular accounts. Don't expire forums.
		$condition = ["NOT `account_expired` AND `account_expires_on` > ? AND `account_expires_on` < UTC_TIMESTAMP() AND `page-flags` = 0", DBA::NULL_DATETIME];
		DBA::update('user', ['account_expired' => true], $condition);

		// Remove any freshly expired account
		$users = DBA::select('user', ['uid'], ['account_expired' => true, 'account_removed' => false]);
		while ($user = DBA::fetch($users)) {
			User::remove($user['uid']);
		}
		DBA::close($users);

		// delete user records for recently removed accounts
		$users = DBA::select('user', ['uid'], ["`account_removed` AND `account_expires_on` < UTC_TIMESTAMP() "]);
		while ($user = DBA::fetch($users)) {
			// Delete the contacts of this user
			$self = DBA::selectFirst('contact', ['nurl'], ['self' => true, 'uid' => $user['uid']]);
			if (DBA::isResult($self)) {
				DBA::delete('contact', ['nurl' => $self['nurl'], 'self' => false]);
			}

			Photo::delete(['uid' => $user['uid']]);

			DBA::delete('user', ['uid' => $user['uid']]);
		}
		DBA::close($users);
	}
}
