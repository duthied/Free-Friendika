<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\ActivityPub;

/**
 * Send updated profile data to Diaspora and ActivityPub
 */
class ProfileUpdate {
	public static function execute($uid = 0) {
		if (empty($uid)) {
			return;
		}

		$a = DI::app();

		$inboxes = ActivityPub\Transmitter::fetchTargetInboxesforUser($uid);

		foreach ($inboxes as $inbox) {
			Logger::log('Profile update for user ' . $uid . ' to ' . $inbox .' via ActivityPub', Logger::DEBUG);
			Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
				'APDelivery', Delivery::PROFILEUPDATE, '', $inbox, $uid);
		}

		Diaspora::sendProfile($uid);
	}
}
