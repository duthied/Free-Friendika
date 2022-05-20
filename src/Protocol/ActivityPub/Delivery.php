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

namespace Friendica\Protocol\ActivityPub;

use Friendica\Core\Logger;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Model\Post;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\HTTPSignature;
use Friendica\Worker\Delivery as WorkerDelivery;

class Delivery
{
	public static function deliver(string $inbox): array
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

	public static function deliverToInbox(string $cmd, int $item_id, string $inbox, int $uid, array $receivers, int $uri_id): array
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

		if ($cmd == WorkerDelivery::MAIL) {
			$data = ActivityPub\Transmitter::createActivityFromMail($item_id);
			if (!empty($data)) {
				$success = HTTPSignature::transmit($data, $inbox, $uid);
			}
		} elseif ($cmd == WorkerDelivery::SUGGESTION) {
			$success = ActivityPub\Transmitter::sendContactSuggestion($uid, $inbox, $item_id);
		} elseif ($cmd == WorkerDelivery::RELOCATION) {
			// @todo Implementation pending
		} elseif ($cmd == WorkerDelivery::POKE) {
			// Implementation not planned
		} elseif ($cmd == WorkerDelivery::REMOVAL) {
			$success = ActivityPub\Transmitter::sendProfileDeletion($uid, $inbox);
		} elseif ($cmd == WorkerDelivery::PROFILEUPDATE) {
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

		if ($success && in_array($cmd, [WorkerDelivery::POST])) {
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
