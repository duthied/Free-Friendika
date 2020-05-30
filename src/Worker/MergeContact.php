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
	 * @param integer $search_cid
	 * @param integer $replace_cid
	 * @param integer $uid
	 */
	public static function execute(int $search_cid, int $replace_cid, int $uid)
	{
		if (empty($search_cid) || empty($replace_cid) || ($search_cid == $replace_cid)) {
			// Invalid request
			return;
		}

		Logger::info('Handling duplicate', ['search' => $replace_cid, 'replace' => $search_cid]);

		// Search and replace
		DBA::update('item', ['contact-id' => $search_cid], ['contact-id' => $replace_cid]);
		DBA::update('thread', ['contact-id' => $search_cid], ['contact-id' => $replace_cid]);
		DBA::update('mail', ['contact-id' => $search_cid], ['contact-id' => $replace_cid]);
		DBA::update('photo', ['contact-id' => $search_cid], ['contact-id' => $replace_cid]);
		DBA::update('event', ['cid' => $search_cid], ['cid' => $replace_cid]);

		// These fields only contain public contact entries (uid = 0)
		if ($uid == 0) {
			DBA::update('post-tag', ['cid' => $search_cid], ['cid' => $replace_cid]);
			DBA::update('item', ['author-id' => $search_cid], ['author-id' => $replace_cid]);
			DBA::update('item', ['owner-id' => $search_cid], ['owner-id' => $replace_cid]);
			DBA::update('thread', ['author-id' => $search_cid], ['author-id' => $replace_cid]);
			DBA::update('thread', ['owner-id' => $search_cid], ['owner-id' => $replace_cid]);
		} else {
			/// @todo Check if some other data needs to be adjusted as well, possibly the "rel" status?
		}

		// Remove the duplicate
		DBA::delete('contact', ['id' => $replace_cid]);
	}
}
