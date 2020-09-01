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

use Friendica\Core\Addon;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Util\DateTimeFormat;

/**
 * Poll contacts for unreceived messages
 */
class PollContacts
{
	public static function execute()
	{
		Addon::reload();

		$sql = "SELECT `contact`.`id`, `contact`.`nick`, `contact`.`name`, `contact`.`network`, `contact`.`archive`,
					`contact`.`last-update`, `contact`.`priority`, `contact`.`rating`, `contact`.`rel`, `contact`.`subhub`
				FROM `user`
				STRAIGHT_JOIN `contact`
				ON `contact`.`uid` = `user`.`uid` AND `contact`.`poll` != ''
					AND `contact`.`network` IN (?, ?, ?, ?, ?)
					AND NOT `contact`.`self` AND NOT `contact`.`blocked`
					AND `contact`.`rel` != ?
				WHERE NOT `user`.`account_expired` AND NOT `user`.`account_removed`";

		$parameters = [Protocol::DFRN, Protocol::ACTIVITYPUB, Protocol::OSTATUS, Protocol::FEED, Protocol::MAIL, Contact::FOLLOWER];

		// Only poll from those with suitable relationships,
		// and which have a polling address and ignore Diaspora since
		// we are unable to match those posts with a Diaspora GUID and prevent duplicates.
		$abandon_days = intval(DI::config()->get('system', 'account_abandon_days'));
		if ($abandon_days < 1) {
			$abandon_days = 0;
		}

		if (!empty($abandon_days)) {
			$sql .= " AND `user`.`login_date` > UTC_TIMESTAMP() - INTERVAL ? DAY";
			$parameters[] = $abandon_days;
		}

		$contacts = DBA::p($sql, $parameters);

		if (!DBA::isResult($contacts)) {
			return;
		}

		while ($contact = DBA::fetch($contacts)) {
			$ratings = [0, 3, 7, 8, 9, 10];
			if (DI::config()->get('system', 'adjust_poll_frequency') && ($contact['network'] == Protocol::FEED)) {
				$rating = $contact['rating'];
			} elseif (array_key_exists($contact['priority'], $ratings)) {
				$rating = $ratings[$contact['priority']];
			} else {
				$rating = -1;
			}

			// Friendica and OStatus are checked once a day
			if (in_array($contact['network'], [Protocol::DFRN, Protocol::OSTATUS])) {
				$rating = 8;
			}

			// ActivityPub is checked once a week
			if ($contact['network'] == Protocol::ACTIVITYPUB) {
				$rating = 9;
			}

			// Check archived contacts once a month
			if ($contact['archive']) {
				$rating = 10;
			}

			if ($rating < 0) {
				continue;
			}
			/*
			 * Based on $contact['priority'], should we poll this site now? Or later?
			 */

			$min_poll_interval = DI::config()->get('system', 'min_poll_interval');

			$poll_intervals = [$min_poll_interval . ' minute', '15 minute', '30 minute',
				'1 hour', '2 hour', '3 hour', '6 hour', '12 hour' ,'1 day', '1 week', '1 month'];

			$now = DateTimeFormat::utcNow();
			$next_update = DateTimeFormat::utc($contact['last-update'] . ' + ' . $poll_intervals[$rating]);

			if (empty($poll_intervals[$rating]) || ($now < $next_update))  {
				Logger::debug('No update', ['cid' => $contact['id'], 'rating' => $rating, 'next' => $next_update, 'now' => $now]);
				continue;
			}

			if ((($contact['network'] == Protocol::FEED) && ($contact['priority'] <= 3)) || ($contact['network'] == Protocol::MAIL)) {
				$priority = PRIORITY_MEDIUM;
			} elseif ($contact['archive']) {
				$priority = PRIORITY_NEGLIGIBLE;
			} else {
				$priority = PRIORITY_LOW;
			}

			Logger::notice("Polling " . $contact["network"] . " " . $contact["id"] . " " . $contact['priority'] . " " . $contact["nick"] . " " . $contact["name"]);

			Worker::add(['priority' => $priority, 'dont_fork' => true, 'force_priority' => true], 'OnePoll', (int)$contact['id']);
		}
		DBA::close($contacts);
	}
}
