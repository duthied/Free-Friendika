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
use Friendica\Model\Subscription as ModelSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushSubscription
{
	public static function execute(int $sid)
	{
		$subscription = DBA::selectFirst('subscription', [], ['id' => $sid]);

		$notification = [
			'subscription' => Subscription::create([
				'endpoint'  => $subscription['endpoint'],
				'publicKey' => $subscription['pubkey'],
				'authToken' => $subscription['secret'],
			]),
			'payload' => null,
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
			$notification['subscription'],
			$notification['payload']
		);

		$endpoint = $report->getRequest()->getUri()->__toString();

		if ($report->isSuccess()) {
			Logger::info('Message sent successfully for subscription', ['endpoint' => $endpoint]);
		} else {
			Logger::info('Message failed to sent for subscription', ['endpoint' => $endpoint, 'reason' => $report->getReason()]);
		}
	}
}
