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
use Friendica\Model\Contact;
use Friendica\Util\Strings;

/**
 * Do some repairs in database entries
 *
 */
class RepairDatabase
{
	public static function execute()
	{
		// Sometimes there seem to be issues where the "self" contact vanishes.
		// We haven't found the origin of the problem by now.

		$users = DBA::select('user', ['uid'], ["NOT EXISTS (SELECT `uid` FROM `contact` WHERE `contact`.`uid` = `user`.`uid` AND `contact`.`self`)"]);
		while ($user = DBA::fetch($users)) {
			Logger::notice('Create missing self contact', ['user'=> $user['uid']]);
			Contact::createSelfFromUserId($user['uid']);
		}
		DBA::close($users);

		// There was an issue where the nick vanishes from the contact table
		DBA::e("UPDATE `contact` INNER JOIN `user` ON `contact`.`uid` = `user`.`uid` SET `nick` = `nickname` WHERE `self` AND `nick`=''");

		/// @todo
		/// - remove thread entries without item
		/// - remove sign entries without item
		/// - remove children when parent got lost
		/// - set contact-id in item when not present

		// Add intro entries for pending contacts
		// We don't do this for DFRN entries since such revived contact requests seem to mostly fail.
		$pending_contacts = DBA::p("SELECT `uid`, `id`, `url`, `network`, `created` FROM `contact`
			WHERE `pending` AND `rel` IN (?, ?) AND `network` != ?
				AND NOT EXISTS (SELECT `id` FROM `intro` WHERE `contact-id` = `contact`.`id`)",
			0, Contact::FOLLOWER, Protocol::DFRN);
		while ($contact = DBA::fetch($pending_contacts)) {
			DBA::insert('intro', ['uid' => $contact['uid'], 'contact-id' => $contact['id'], 'blocked' => false,
				'hash' => Strings::getRandomHex(), 'datetime' => $contact['created']]);
		}
		DBA::close($pending_contacts);
	}
}
