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

namespace Friendica\Module\Api\Twitter;

use Friendica\Core\System;
use Friendica\Model\Contact;

/**
 * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-followers-ids
 */
class FollowersIds extends ContactEndpoint
{
	public static function rawContent(array $parameters = [])
	{
		// Expected value for user_id parameter: public/user contact id
		$contact_id    = $_GET['user_id'] ?? null;
		$screen_name   = $_GET['screen_name'] ?? null;
		$cursor        = $_GET['cursor'] ?? $_GET['since_id'] ?? -1;
		$stringify_ids = ($_GET['stringify_ids'] ?? 'false') != 'false';
		$count         = min((int) ($_GET['count'] ?? self::DEFAULT_COUNT), self::MAX_COUNT);

		System::jsonExit(self::ids(
			[Contact::FOLLOWER, Contact::FRIEND],
			self::getUid($contact_id, $screen_name),
			$cursor,
			$count,
			$stringify_ids
		));
	}
}
