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

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Protocol\ActivityPub;
class APDelivery
{
	/**
	 * Delivers ActivityPub messages
	 *
	 * @param string  $cmd       One of the Worker\Delivery constant values
	 * @param integer $item_id   0 if no item is involved (like Delivery::REMOVAL and Delivery::PROFILEUPDATE)
	 * @param string  $inbox     The URL of the recipient profile
	 * @param integer $uid       The ID of the user who triggered this delivery
	 * @param array   $receivers The contact IDs related to the inbox URL for contact archival housekeeping
	 * @param int     $uri_id    URI-ID of item to be transmitted
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function execute(string $cmd, int $item_id, string $inbox, int $uid, array $receivers = [], int $uri_id = 0)
	{
		if (ActivityPub\Transmitter::archivedInbox($inbox)) {
			Logger::info('Inbox is archived', ['cmd' => $cmd, 'inbox' => $inbox, 'id' => $item_id, 'uri-id' => $uri_id, 'uid' => $uid]);
			if (empty($uri_id) && !empty($item_id)) {
				$item = Post::selectFirst(['uri-id'], ['id' => $item_id]);
				$uri_id = $item['uri-id'] ?? 0;
			}
			if (empty($uri_id)) {
				$posts   = Post\Delivery::selectForInbox($inbox);
				$uri_ids = array_column($posts, 'uri-id');
			} else {
				$uri_ids = [$uri_id];
			}

			foreach ($uri_ids as $uri_id) {
				Post\Delivery::remove($uri_id, $inbox);
				Post\DeliveryData::incrementQueueFailed($uri_id);
			}
			return;
		}

		Logger::debug('Invoked', ['cmd' => $cmd, 'inbox' => $inbox, 'id' => $item_id, 'uri-id' => $uri_id, 'uid' => $uid]);

		if (empty($uri_id)) {
			$result  = ActivityPub\Delivery::deliver($inbox);
			$success = $result['success'];
			$drop    = false;
			$uri_ids = $result['uri_ids'];
		} else {
			$owner = User::getOwnerDataById($uid);
			if (!$owner) {
				Post\Delivery::remove($uri_id, $inbox);
				Post\Delivery::incrementFailed($uri_id, $inbox);
				return;
			}

			$result  = ActivityPub\Delivery::deliverToInbox($cmd, $item_id, $inbox, $owner, $receivers, $uri_id);
			$success = $result['success'];
			$drop    = $result['drop'];
			$uri_ids = [$uri_id];
		}

		if (!$drop && !$success && !Worker::defer() && !empty($uri_ids)) {
			foreach ($uri_ids as $uri_id) {
				Post\Delivery::remove($uri_id, $inbox);
				Post\DeliveryData::incrementQueueFailed($uri_id);
			}
		}
	}
}
