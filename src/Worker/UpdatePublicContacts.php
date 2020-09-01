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
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;

/**
 * Update public contacts
 */
class UpdatePublicContacts
{
	public static function execute()
	{
		$count = 0;
		$last_updated = DateTimeFormat::utc('now - 1 week');
		$condition = ["`network` IN (?, ?, ?, ?) AND `uid` = ? AND NOT `self` AND `last-update` < ?",
			Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, 0, $last_updated];

		$oldest_date = '';
		$oldest_id = '';
		$contacts = DBA::select('contact', ['id', 'last-update'], $condition, ['limit' => 100, 'order' => ['last-update']]);
		while ($contact = DBA::fetch($contacts)) {
			if (empty($oldest_id)) {
				$oldest_id = $contact['id'];
				$oldest_date = $contact['last-update'];
			}
			Worker::add(PRIORITY_LOW, "UpdateContact", $contact['id']);
			++$count;
		}
		Logger::info('Initiated update for public contacts', ['interval' => $count, 'id' => $oldest_id, 'oldest' => $oldest_date]);
		DBA::close($contacts);
	}
}
