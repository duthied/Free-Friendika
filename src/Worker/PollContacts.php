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
		$abandon_days = intval(DI::config()->get('system', 'account_abandon_days'));
		if ($abandon_days < 1) {
			$abandon_days = 0;
		}

		$condition = ['network' => [Protocol::DFRN, Protocol::ACTIVITYPUB, Protocol::OSTATUS, Protocol::FEED, 
			Protocol::MAIL, Protocol::ZOT, Protocol::PHANTOM], 'self' => false, 'blocked' => false];

		if (!empty($abandon_days)) {
			$condition = DBA::mergeConditions($condition,
				["`uid` != ? AND `uid` IN (SELECT `uid` FROM `user` WHERE NOT `account_expired` AND NOT `account_removed`  AND `login_date` > UTC_TIMESTAMP() - INTERVAL ? DAY)", 0, $abandon_days]);
		} else 	{
			$condition = DBA::mergeConditions($condition,
				["`uid` != ? AND `uid` IN (SELECT `uid` FROM `user` WHERE NOT `account_expired` AND NOT `account_removed`)", 0]);
		}

		$contacts = DBA::select('contact', ['id', 'nick', 'name', 'network', 'archive', 'last-update', 'priority', 'rating'], $condition);
		if (!DBA::isResult($contacts)) {
			return;
		}

		while ($contact = DBA::fetch($contacts)) {
			if (in_array($contact['network'], [Protocol::MAIL, Protocol::FEED])) {
				$ratings = [0, 3, 7, 8, 9, 10];
				if (DI::config()->get('system', 'adjust_poll_frequency') && ($contact['network'] == Protocol::FEED)) {
					$rating = $contact['rating'];
				} elseif (array_key_exists($contact['priority'], $ratings)) {
					$rating = $ratings[$contact['priority']];
				} else {
					$rating = -1;
				}
			} else {
				// Check once a week per default for all other networks
				$rating = 9;
			}

			// Friendica and OStatus are checked once a day
			if (in_array($contact['network'], [Protocol::DFRN, Protocol::OSTATUS])) {
				$rating = 8;
			}

			// Check archived contacts or contacts with unsupported protocols once a month
			if ($contact['archive'] || in_array($contact['network'], [Protocol::ZOT, Protocol::PHANTOM])) {
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
