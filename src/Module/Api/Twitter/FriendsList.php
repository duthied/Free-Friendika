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
 * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-friends-list
 */
class FriendsList extends ContactEndpoint
{
	public static function rawContent(array $parameters = [])
	{
		// Expected value for user_id parameter: public/user contact id
		$contact_id    = filter_input(INPUT_GET, 'user_id'      , FILTER_VALIDATE_INT);
		$screen_name   = filter_input(INPUT_GET, 'screen_name');
		$cursor        = filter_input(INPUT_GET, 'cursor'       , FILTER_VALIDATE_INT);
		$count         = filter_input(INPUT_GET, 'count'        , FILTER_VALIDATE_INT, ['options' => [
			'default' => self::DEFAULT_COUNT,
			'min_range' => 1,
			'max_range' => self::MAX_COUNT,
		]]);
		$skip_status           = filter_input(INPUT_GET, 'skip_status'          , FILTER_VALIDATE_BOOLEAN);
		$include_user_entities = filter_input(INPUT_GET, 'include_user_entities', FILTER_VALIDATE_BOOLEAN);

		// Friendica-specific
		$since_id      = filter_input(INPUT_GET, 'since_id'     , FILTER_VALIDATE_INT);
		$max_id        = filter_input(INPUT_GET, 'max_id'       , FILTER_VALIDATE_INT, ['options' => [
			'default' => 1,
		]]);

		System::jsonExit(self::list(
			[Contact::SHARING, Contact::FRIEND],
			self::getUid($contact_id, $screen_name),
			$cursor ?? $since_id ?? - $max_id,
			$count,
			$skip_status,
			$include_user_entities
		));
	}
}
