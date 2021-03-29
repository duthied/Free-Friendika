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

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Photo;

/**
 * Removes cached avatars from public contacts that aren't in use
 */
class RemoveUnusedAvatars
{
	public static function execute()
	{
		$condition = ["`uid` = ? AND NOT `self` AND NOT `nurl` IN (SELECT `nurl` FROM `contact` WHERE `uid` != ?)
			AND `id` IN (SELECT `contact-id` FROM `photo`) AND NOT `id` IN (SELECT `author-id` FROM `post-user`)
			AND NOT `id` IN (SELECT `owner-id` FROM `post-user`) AND NOT `id` IN (SELECT `causer-id` FROM `post-user`)
			AND NOT `id` IN (SELECT `cid` FROM `post-tag`) AND NOT `id` IN (SELECT `contact-id` FROM `post-user`)", 0, 0];

		$total = DBA::count('contact', $condition);
		Logger::notice('Starting removal', ['total' => $total]);
		$count = 0;
		$contacts = DBA::select('contact', ['id'], $condition);
		while ($contact = DBA::fetch($contacts)) {
			DBA::update('contact', ['photo' => '', 'thumb' => '', 'micro' => ''], ['id' => $contact['id']]);
			Photo::delete(['contact-id' => $contact['id'], 'album' => Photo::CONTACT_PHOTOS]);
			if ((++$count % 1000) == 0) {
				if (!Worker::isInMaintenanceWindow()) {
					Logger::notice('We are outside of the maintenance window, quitting');
					return;
				}
				Logger::notice('In removal', ['count' => $count, 'total' => $total]);
			}
		}
		DBA::close($contacts);
		Logger::notice('Removal done', ['count' => $count, 'total' => $total]);
	}
}
