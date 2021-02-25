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
use Friendica\Database\DBStructure;
use Friendica\Model\Photo;
use Friendica\Model\Post;

/**
 * Removes orphaned data from deleted contacts
 */
class RemoveContact {
	public static function execute($id) {
		if (empty($id)) {
			return;
		}

		// Only delete if the contact is to be deleted
		$contact = DBA::selectFirst('contact', ['id', 'uid', 'url', 'nick', 'name'], ['deleted' => true, 'id' => $id]);
		if (!DBA::isResult($contact)) {
			return;
		}

		Logger::info('Start deleting contact', ['contact' => $contact]);

		// Now we delete the contact and all depending tables
		DBA::delete('post-tag', ['cid' => $id]);

		if (DBStructure::existsTable('item')) {
			DBA::delete('item', ['author-id' => $id]);
			DBA::delete('item', ['owner-id' => $id]);
			DBA::delete('item', ['causer-id' => $id]);
			DBA::delete('item', ['contact-id' => $id]);
		}

		DBA::delete('mail', ['contact-id' => $id]);

		Post\ThreadUser::delete(['author-id' => $id]);
		Post\ThreadUser::delete(['owner-id' => $id]);
		Post\ThreadUser::delete(['causer-id' => $id]);
		Post\ThreadUser::delete(['contact-id' => $id]);
		Post\Thread::delete(['author-id' => $id]);
		Post\Thread::delete(['owner-id' => $id]);
		Post\Thread::delete(['causer-id' => $id]);
		Post\User::delete(['author-id' => $id]);
		Post\User::delete(['owner-id' => $id]);
		Post\User::delete(['causer-id' => $id]);
		Post\User::delete(['contact-id' => $id]);
		Post::delete(['author-id' => $id]);
		Post::delete(['owner-id' => $id]);
		Post::delete(['causer-id' => $id]);

		Photo::delete(['contact-id' => $id]);
		$ret = DBA::delete('contact', ['id' => $id]);
		Logger::info('Deleted contact', ['id' => $id, 'result' => $ret]);
	}
}
