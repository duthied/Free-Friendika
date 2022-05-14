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
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
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
			$result  = self::deliver($inbox);
			$success = $result['success'];
			$uri_ids = $result['uri_ids'];
		} else {
			$result  = self::deliverToInbox($cmd, $item_id, $inbox, $uid, $receivers, $uri_id);
			$success = $result['success'];
			$uri_ids = [$uri_id];
		}

		if (!$success && !Worker::defer() && !empty($uri_ids)) {
			foreach ($uri_ids as $uri_id) {
				Post\Delivery::remove($uri_id, $inbox);
				Post\DeliveryData::incrementQueueFailed($uri_id);
			}
		}
	}

	private static function deliver(string $inbox):array
	{
		$uri_ids    = [];
		$posts      = Post\Delivery::selectForInbox($inbox);
		$serverfail = false;

		foreach ($posts as $post) {
			if (!$serverfail) {
				$result = self::deliverToInbox($post['command'], 0, $inbox, $post['uid'], $post['receivers'], $post['uri-id']);

				if ($result['serverfailure']) {
					// In a timeout situation we assume that every delivery to that inbox will time out.
					// So we set the flag and try all deliveries at a later time.
					Logger::info('Inbox delivery has a server failure', ['inbox' => $inbox]);
					$serverfail = true;
				}
			}

			if ($serverfail || !$result['success']) {
				$uri_ids[] = $post['uri-id'];
			}
		}

		Logger::debug('Inbox delivery done', ['inbox' => $inbox, 'posts' => count($posts), 'failed' => count($uri_ids), 'serverfailure' => $serverfail]);
		return ['success' => empty($uri_ids), 'uri_ids' => $uri_ids];
	}

	private static function deliverToInbox(string $cmd, int $item_id, string $inbox, int $uid, array $receivers, int $uri_id): array
	{
		if (empty($item_id) && !empty($uri_id) && !empty($uid)) {
			$item = Post::selectFirst(['id', 'parent', 'origin'], ['uri-id' => $uri_id, 'uid' => [$uid, 0]], ['order' => ['uid' => true]]);
			if (empty($item['id'])) {
				Logger::notice('Item not found, removing delivery', ['uri-id' => $uri_id, 'uid' => $uid, 'cmd' => $cmd, 'inbox' => $inbox]);
				Post\Delivery::remove($uri_id, $inbox);
				return true;
			} else {
				$item_id = $item['id'];
			}
		}

		$success    = true;
		$serverfail = false;

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
				$timestamp  = microtime(true);
				$response   = HTTPSignature::post($data, $inbox, $uid);
				$runtime    = microtime(true) - $timestamp;
				$success    = $response->isSuccess();
				$serverfail = $response->isTimeout();
				if (!$success) {
					if (!$serverfail && ($response->getReturnCode() >= 500) && ($response->getReturnCode() <= 599)) {
						$serverfail = true;
					}

					$xrd_timeout = DI::config()->get('system', 'xrd_timeout');
					if (!$serverfail && $xrd_timeout && ($runtime > $xrd_timeout)) {
						$serverfail = true;
					}
					$curl_timeout = DI::config()->get('system', 'curl_timeout');
					if (!$serverfail && $curl_timeout && ($runtime > $curl_timeout)) {
						$serverfail = true;
					}

					Logger::info('Delivery failed', ['retcode' => $response->getReturnCode(), 'serverfailure' => $serverfail, 'runtime' => round($runtime, 3), 'uri-id' => $uri_id, 'uid' => $uid, 'item_id' => $item_id, 'cmd' => $cmd, 'inbox' => $inbox]);
				}
				if ($uri_id) {
					if ($success) {
						Post\Delivery::remove($uri_id, $inbox);
					} else {
						Post\Delivery::incrementFailed($uri_id, $inbox);
					}
				}
			}
		}

		self::setSuccess($receivers, $success);

		Logger::debug('Delivered', ['uri-id' => $uri_id, 'uid' => $uid, 'item_id' => $item_id, 'cmd' => $cmd, 'inbox' => $inbox, 'success' => $success]);

		if ($success && in_array($cmd, [Delivery::POST])) {
			Post\DeliveryData::incrementQueueDone($uri_id, Post\DeliveryData::ACTIVITYPUB);
		}

		return ['success' => $success, 'serverfailure' => $serverfail];
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
