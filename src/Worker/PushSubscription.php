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

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Subscription as ModelSubscription;
use Friendica\Util\DateTimeFormat;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushSubscription
{
	public static function execute(int $sid, int $nid)
	{
		$subscription = DBA::selectFirst('subscription', [], ['id' => $sid]);
		$notification = DBA::selectFirst('notification', [], ['id' => $nid]);

		if (!empty($notification['uri-id'])) {
			$notify = DBA::selectFirst('notify', ['msg'], ['uri-id' => $notification['target-uri-id']]);
		}

		if (!empty($notification['actor-id'])) {
			$actor = Contact::getById($notification['actor-id']);
		}

		$push = [
			'subscription' => Subscription::create([
				'endpoint'  => $subscription['endpoint'],
				'publicKey' => $subscription['pubkey'],
				'authToken' => $subscription['secret'],
			]),
			// @todo Check if we are supposed to transmit a payload at all
			'payload' => json_encode([
				'title'     => 'Friendica',
				'body'      => $notify['msg'] ?? '',
				'icon'      => $actor['thumb'] ?? '',
				'image'     => '',
				'badge'     => DI::baseUrl()->get() . '/images/friendica-192.png',
				'tag'       => $notification['parent-uri-id'] ?? '',
				'timestamp' => DateTimeFormat::utc($notification['created'], DateTimeFormat::JSON),
			]),
		];

		$auth = [
			'VAPID' => [
				'subject'    => DI::baseUrl()->getHostname(),
				'publicKey'  => ModelSubscription::getPublicVapidKey(),
				'privateKey' => ModelSubscription::getPrivateVapidKey(),
			],
		];

		$webPush = new WebPush($auth);

		$report = $webPush->sendOneNotification(
			$push['subscription'],
			$push['payload']
		);

		$endpoint = $report->getRequest()->getUri()->__toString();

		if ($report->isSuccess()) {
			Logger::info('Message sent successfully for subscription', ['endpoint' => $endpoint]);
		} else {
			Logger::info('Message failed to sent for subscription', ['endpoint' => $endpoint, 'reason' => $report->getReason()]);
		}
	}
}
