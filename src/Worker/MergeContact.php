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

use Friendica\Core\Logger;
use Friendica\Database\DBA;

class MergeContact
{
	public static function execute($first, $dup_id, $uid)
	{
		if (empty($first) || empty($dup_id) || ($first == $dup_id)) {
			// Invalid request
			return;
		}

		Logger::info('Handling duplicate', ['search' => $dup_id, 'replace' => $first]);

		// Search and replace
		DBA::update('item', ['contact-id' => $first], ['contact-id' => $dup_id]);
		DBA::update('thread', ['contact-id' => $first], ['contact-id' => $dup_id]);
		DBA::update('mail', ['contact-id' => $first], ['contact-id' => $dup_id]);
		DBA::update('photo', ['contact-id' => $first], ['contact-id' => $dup_id]);
		DBA::update('event', ['cid' => $first], ['cid' => $dup_id]);
		if ($uid == 0) {
			DBA::update('item', ['author-id' => $first], ['author-id' => $dup_id]);
			DBA::update('item', ['owner-id' => $first], ['owner-id' => $dup_id]);
			DBA::update('thread', ['author-id' => $first], ['author-id' => $dup_id]);
			DBA::update('thread', ['owner-id' => $first], ['owner-id' => $dup_id]);
		} else {
			/// @todo Check if some other data needs to be adjusted as well, possibly the "rel" status?
		}

		// Remove the duplicate
		DBA::delete('contact', ['id' => $dup_id]);
	}
}
