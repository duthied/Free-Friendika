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

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;

class Conversation extends BaseFactory
{
	public function CreateFromConvId(int $id)
	{
		$accounts    = [];
		$unread      = false;
		$last_status = null;

		$ids = [];

		$mails = DBA::select('mail', ['id', 'from-url', 'uid', 'seen'], ['convid' => $id], ['order' => ['id' => true]]);
		while ($mail = DBA::fetch($mails)) {
			if (!$mail['seen']) {
				$unread = true;
			}

			$id = Contact::getIdForURL($mail['from-url'], 0, false);
			if (in_array($id, $ids)) {
				continue;
			}

			$ids[] = $id;

			if (empty($last_status)) {
				$last_status = DI::mstdnStatus()->createFromMailId($mail['id']);
			}

			$accounts[] = DI::mstdnAccount()->createFromContactId($id, 0);
		}

		return new \Friendica\Object\Api\Mastodon\Conversation($id, $accounts, $unread, $last_status);
	}
}
