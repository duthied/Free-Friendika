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
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Model\Photo;

/**
 * Removes public contacts that aren't in use
 */
class RemoveUnusedContacts {
	public static function execute() {
		$condition = ["`uid` = ? AND NOT `self` AND NOT `nurl` IN (SELECT `nurl` FROM `contact` WHERE `uid` != ?)
			AND (NOT `network` IN (?, ?, ?, ?, ?, ?) OR (`archive` AND `success_update` < UTC_TIMESTAMP() - INTERVAL ? DAY))
			AND NOT `id` IN (SELECT `author-id` FROM `item`) AND NOT `id` IN (SELECT `owner-id` FROM `item`)
			AND NOT `id` IN (SELECT `causer-id` FROM `item`) AND NOT `id` IN (SELECT `cid` FROM `post-tag`)
			AND NOT `id` IN (SELECT `contact-id` FROM `item`) AND NOT `id` IN (SELECT `author-id` FROM `thread`)
			AND NOT `id` IN (SELECT `owner-id` FROM `thread`) AND NOT `id` IN (SELECT `contact-id` FROM `thread`)
			AND NOT `id` IN (SELECT `contact-id` FROM `post-user`) AND NOT `id` IN (SELECT `cid` FROM `user-contact`) 
			AND NOT `id` IN (SELECT `cid` FROM `event`) AND NOT `id` IN (SELECT `contact-id` FROM `group_member`)",
			0, 0, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, Protocol::FEED, Protocol::MAIL, Protocol::ACTIVITYPUB, 365];

		$total = DBA::count('contact', $condition);
		Logger::notice('Starting removal', ['total' => $total]);
		$count = 0;
		$contacts = DBA::select('contact', ['id', 'uid'], $condition);
		while ($contact = DBA::fetch($contacts)) {
			if (Photo::delete(['uid' => $contact['uid'], 'contact-id' => $contact['id']])) {
				DBA::delete('contact', ['id' => $contact['id']]);
				if ((++$count % 1000) == 0) {
					Logger::notice('In removal', ['count' => $count, 'total' => $total]);
				}
			}
		}
		DBA::close($contacts);
		Logger::notice('Removal done', ['count' => $count, 'total' => $total]);
	}
}
