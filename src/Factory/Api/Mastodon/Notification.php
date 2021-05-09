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
use Friendica\Model\Notification as ModelNotification;

class Notification extends BaseFactory
{
	public function create(int $id)
	{
		$notification = DBA::selectFirst('notify', [], ['id' => $id]);
		if (!DBA::isResult($notification)) {
			return null;
		}

		$cid = Contact::getIdForURL($notification['url'], 0, false);
		if (empty($cid)) {
			return null;
		}

		/*
		follow         = Someone followed you
		follow_request = Someone requested to follow you
		mention        = Someone mentioned you in their status
		reblog         = Someone boosted one of your statuses
		favourite      = Someone favourited one of your statuses
		poll           = A poll you have voted in or created has ended
		status         = Someone you enabled notifications for has posted a status
		*/

		switch ($notification['type']) {
			case ModelNotification\Type::INTRO:
				$type = 'follow_request';
				break;

			case ModelNotification\Type::WALL:
			case ModelNotification\Type::COMMENT:
			case ModelNotification\Type::MAIL:
			case ModelNotification\Type::TAG_SELF:
			case ModelNotification\Type::POKE:
				$type = 'mention';
				break;

			case ModelNotification\Type::SHARE:
				$type = 'status';
				break;

			default:
				return null;
		}

		$account = DI::mstdnAccount()->createFromContactId($cid);

		if (!empty($notification['uri-id'])) {
			try {
				$status = DI::mstdnStatus()->createFromUriId($notification['uri-id'], $notification['uid']);
			} catch (\Throwable $th) {
				$status = null;
			}
		} else {
			$status = null;
		}

		return new \Friendica\Object\Api\Mastodon\Notification($id, $type, $notification['date'], $account, $status);
	}
}
