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
	/**
	 * Replace all occurences of the given contact id and replace it
	 *
	 * @param integer $new_cid
	 * @param integer $old_cid
	 * @param integer $uid
	 */
	public static function execute(int $new_cid, int $old_cid, int $uid)
	{
		if (empty($new_cid) || empty($old_cid) || ($new_cid == $old_cid)) {
			// Invalid request
			return;
		}

		Logger::info('Handling duplicate', ['search' => $old_cid, 'replace' => $new_cid]);

		// Search and replace
		DBA::update('item', ['contact-id' => $new_cid], ['contact-id' => $old_cid]);
		DBA::update('thread', ['contact-id' => $new_cid], ['contact-id' => $old_cid]);
		DBA::update('mail', ['contact-id' => $new_cid], ['contact-id' => $old_cid]);
		DBA::update('photo', ['contact-id' => $new_cid], ['contact-id' => $old_cid]);
		DBA::update('event', ['cid' => $new_cid], ['cid' => $old_cid]);

		// These fields only contain public contact entries (uid = 0)
		if ($uid == 0) {
			DBA::update('post-tag', ['cid' => $new_cid], ['cid' => $old_cid]);
			DBA::update('item', ['author-id' => $new_cid], ['author-id' => $old_cid]);
			DBA::update('item', ['owner-id' => $new_cid], ['owner-id' => $old_cid]);
			DBA::update('thread', ['author-id' => $new_cid], ['author-id' => $old_cid]);
			DBA::update('thread', ['owner-id' => $new_cid], ['owner-id' => $old_cid]);
		} else {
			/// @todo Check if some other data needs to be adjusted as well, possibly the "rel" status?
		}

		// Remove the duplicate
		DBA::delete('contact', ['id' => $old_cid]);
	}
}
