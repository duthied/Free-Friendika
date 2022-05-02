<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\HTTPSignature;

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
			Logger::info('Inbox is archived', ['cmd' => $cmd, 'inbox' => $inbox, 'id' => $item_id, 'uid' => $uid]);
			if (in_array($cmd, [Delivery::POST])) {
				$item = Post::selectFirst(['uri-id'], ['id' => $item_id]);
				Post\DeliveryData::incrementQueueFailed($item['uri-id'] ?? 0);
			}
			return;
		}

		Logger::info('Invoked', ['cmd' => $cmd, 'inbox' => $inbox, 'id' => $item_id, 'uri-id' => $uri_id, 'uid' => $uid]);

		if (empty($uri_id)) {
			$result = self::deliver($inbox);
			$success = $result['success'];
			$uri_ids = $result['uri_ids'];
		}

		if (empty($uri_ids)) {
			$success = self::deliverToInbox($cmd, $item_id, $inbox, $uid, $receivers, $uri_id);
		}

		if (!$success && !Worker::defer() && in_array($cmd, [Delivery::POST])) {
			if ($uri_id) {
				Post\Delivery::remove($uri_id, $inbox);
				Post\DeliveryData::incrementQueueFailed($uri_id);
			} elseif (!empty($uri_ids)) {
				foreach ($uri_ids as $uri_id) {
					Post\Delivery::remove($uri_id, $inbox);
					Post\DeliveryData::incrementQueueFailed($uri_id);
				}
			}
		} elseif ($success && in_array($cmd, [Delivery::POST])) {
			if ($uri_id) {
				Post\DeliveryData::incrementQueueDone($uri_id, Post\DeliveryData::ACTIVITYPUB);
			} elseif (!empty($uri_ids)) {
				foreach ($uri_ids as $uri_id) {
					Post\DeliveryData::incrementQueueDone($uri_id, Post\DeliveryData::ACTIVITYPUB);
				}
			}
		}
	}

	private static function deliver(string $inbox)
	{
		$uri_ids = [];
		$success = true;

		$posts = Post\Delivery::selectForInbox($inbox);
		foreach ($posts as $post) {
			$uri_ids[] = $post['uri-id'];
			if ($success) {
				$success = self::deliverToInbox($post['command'], 0, $inbox, $post['uid'], [], $post['uri-id']);
			}
		}

		return ['success' => $success, 'uri_ids' => $uri_ids];
	}

	private static function deliverToInbox(string $cmd, int $item_id, string $inbox, int $uid, array $receivers, int $uri_id)
	{
		if (empty($item_id) && !empty($uri_id) && !empty($uid)) {
			$item = Post::selectFirst(['id', 'parent'], ['uri-id' => $uri_id, 'uid' => $uid]);
			$item_id = $item['id'] ?? 0;
			if (empty($receivers) && !empty($item)) {
				$parent = Post::selectFirst(Item::DELIVER_FIELDLIST, ['id' => $item['parent']]);

				$inboxes = ActivityPub\Transmitter::fetchTargetInboxes($parent, $uid, true, $item['id']);

				$receivers = $inboxes[$inbox] ?? [];
			}
		}

		$success = true;

		if ($cmd == Delivery::MAIL) {
			$data = ActivityPub\Transmitter::createActivityFromMail($item_id);
			if (!empty($data)) {
				$success = HTTPSignature::transmit($data, $inbox, $uid);
			}
		} elseif ($cmd == Delivery::SUGGESTION) {
			$success = ActivityPub\Transmitter::sendContactSuggestion($uid, $inbox, $item_id);
		} elseif ($cmd == Delivery::RELOCATION) {
			// @todo Implementation pending
		} elseif ($cmd == Delivery::POKE) {
			// Implementation not planned
		} elseif ($cmd == Delivery::REMOVAL) {
			$success = ActivityPub\Transmitter::sendProfileDeletion($uid, $inbox);
		} elseif ($cmd == Delivery::PROFILEUPDATE) {
			$success = ActivityPub\Transmitter::sendProfileUpdate($uid, $inbox);
		} else {
			$data = ActivityPub\Transmitter::createCachedActivityFromItem($item_id);
			if (!empty($data)) {
				$success = HTTPSignature::transmit($data, $inbox, $uid);
				if ($success && $uri_id) {
					Post\Delivery::remove($uri_id, $inbox);
				}
			}
		}

		self::setSuccess($receivers, $success);

		Logger::info('Delivered', ['cmd' => $cmd, 'inbox' => $inbox, 'id' => $item_id, 'uri-id' => $uri_id, 'uid' => $uid, 'success' => $success]);

		return $success;
	}

	private static function setSuccess(array $receivers, bool $success)
	{
		$gsid = null;

		foreach ($receivers as $receiver) {
			$contact = Contact::getById($receiver);
			if (empty($contact)) {
				continue;
			}

			$gsid = $gsid ?: $contact['gsid'];

			if ($success) {
				Contact::unmarkForArchival($contact);
			} else {
				Contact::markForArchival($contact);
			}
		}

		if (!empty($gsid)) {
			GServer::setProtocol($gsid, Post\DeliveryData::ACTIVITYPUB);
		}
	}
}
