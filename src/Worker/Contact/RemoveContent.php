<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Worker\Contact;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Model\Photo;
use Friendica\Model\Post;

/**
 * Removes all content related to the given contact id, doesn't remove the contact itself
 */
class RemoveContent
{
	public static function execute(int $id): bool
	{
		if (empty($id)) {
			return false;
		}

		Logger::info('Start deleting contact content', ['cid' => $id]);

		// Now we delete the contact and all depending tables
		DBA::delete('post-tag', ['cid' => $id]);

		if (DBStructure::existsTable('item')) {
			DBA::delete('item', ['author-id' => $id]);
			DBA::delete('item', ['owner-id' => $id]);
			DBA::delete('item', ['causer-id' => $id]);
			DBA::delete('item', ['contact-id' => $id]);
		}

		DBA::delete('mail', ['contact-id' => $id]);
		DBA::delete('mail', ['author-id' => $id]);

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

		DBA::delete('contact-relation', ['relation-cid' => $id]);
		DBA::delete('contact-relation', ['cid' => $id]);
		DBA::delete('event', ['cid' => $id]);
		DI::fsuggest()->delete(DI::fsuggest()->selectForContact($id));
		DBA::delete('post-tag', ['cid' => $id]);
		DBA::delete('user-contact', ['cid' => $id]);

		DBA::delete('group_member', ['contact-id' => $id]);
		DI::intro()->delete(DI::introFactory()->createDummy($id));

		return true;
	}
}
