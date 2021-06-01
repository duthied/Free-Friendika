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
use Friendica\Model\Post;
use Friendica\Model\Verb;
use Friendica\Protocol\Activity;

class Notification extends BaseFactory
{
	public function createFromNotificationId(int $id)
	{
		$notification = DBA::selectFirst('notification', [], ['id' => $id]);
		if (!DBA::isResult($notification)) {
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

		if (($notification['vid'] == Verb::getID(Activity::ANNOUNCE)) &&
			in_array($notification['type'], [Post\UserNotification::NOTIF_DIRECT_COMMENT, Post\UserNotification::NOTIF_DIRECT_THREAD_COMMENT])) {
			$type = 'reblog';
		} elseif (in_array($notification['vid'], [Verb::getID(Activity::LIKE), Verb::getID(Activity::DISLIKE)]) &&
			in_array($notification['type'], [Post\UserNotification::NOTIF_DIRECT_COMMENT, Post\UserNotification::NOTIF_DIRECT_THREAD_COMMENT])) {
			$type = 'favourite';
		} elseif ($notification['type'] == Post\UserNotification::NOTIF_SHARED) {
			$type = 'status';
		} elseif (in_array($notification['type'], [Post\UserNotification::NOTIF_EXPLICIT_TAGGED,
			Post\UserNotification::NOTIF_IMPLICIT_TAGGED, Post\UserNotification::NOTIF_DIRECT_COMMENT,
			Post\UserNotification::NOTIF_DIRECT_THREAD_COMMENT, Post\UserNotification::NOTIF_THREAD_COMMENT])) {
			$type = 'mention';
		} else {
			return null;
		}

		$account = DI::mstdnAccount()->createFromContactId($notification['actor-id']);

		if (!empty($notification['target-uri-id'])) {
			try {
				$status = DI::mstdnStatus()->createFromUriId($notification['target-uri-id'], $notification['uid']);
			} catch (\Throwable $th) {
				$status = null;
			}
		} else {
			$status = null;
		}

		return new \Friendica\Object\Api\Mastodon\Notification($id, $type, $notification['created'], $account, $status);
	}
}
