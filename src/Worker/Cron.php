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
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Util\DateTimeFormat;

class Cron
{
	public static function execute()
	{
		$a = DI::app();

		$last = DI::config()->get('system', 'last_cron');

		$poll_interval = intval(DI::config()->get('system', 'cron_interval'));

		if ($last) {
			$next = $last + ($poll_interval * 60);
			if ($next > time()) {
				Logger::log('cron intervall not reached');
				return;
			}
		}

		Logger::log('cron: start');

		// Fork the cron jobs in separate parts to avoid problems when one of them is crashing
		Hook::fork($a->queue['priority'], "cron");

		// run the process to update server directories in the background
		Worker::add(PRIORITY_LOW, 'UpdateServerDirectories');

		// Expire and remove user entries
		Worker::add(PRIORITY_MEDIUM, 'ExpireAndRemoveUsers');

		// Call possible post update functions
		Worker::add(PRIORITY_LOW, 'PostUpdate');

		// Repair entries in the database
		Worker::add(PRIORITY_LOW, 'RepairDatabase');

		// once daily run birthday_updates and then expire in background
		$d1 = DI::config()->get('system', 'last_expire_day');
		$d2 = intval(DateTimeFormat::utcNow('d'));

		// Daily cron calls
		if ($d2 != intval($d1)) {

			Worker::add(PRIORITY_LOW, 'UpdateContactBirthdays');

			Worker::add(PRIORITY_LOW, 'UpdatePhotoAlbums');

			// update nodeinfo data
			Worker::add(PRIORITY_LOW, 'NodeInfo');

			Worker::add(PRIORITY_LOW, 'UpdateGServers');

			Worker::add(PRIORITY_LOW, 'Expire');

			Worker::add(PRIORITY_MEDIUM, 'DBClean');

			// check upstream version?
			Worker::add(PRIORITY_LOW, 'CheckVersion');

			self::checkdeletedContacts();

			if (DI::config()->get('system', 'optimize_tables')) {
				self::optimizeTables();
			}
	
			DI::config()->set('system', 'last_expire_day', $d2);
		}

		// Hourly cron calls
		if (DI::config()->get('system', 'last_cron_hourly', 0) + 3600 < time()) {

			// Search for new contacts in the directory
			if (DI::config()->get('system', 'synchronize_directory')) {
				Worker::add(PRIORITY_LOW, 'PullDirectory');
			}

			// Delete all done workerqueue entries
			DBA::delete('workerqueue', ['`done` AND `executed` < UTC_TIMESTAMP() - INTERVAL 1 HOUR']);

			// Optimizing this table only last seconds
			if (DI::config()->get('system', 'optimize_tables')) {
				// We are acquiring the two locks from the worker to avoid locking problems
				if (DI::lock()->acquire(Worker::LOCK_PROCESS, 10)) {
					if (DI::lock()->acquire(Worker::LOCK_WORKER, 10)) {
						DBA::e("OPTIMIZE TABLE `workerqueue`");
						DBA::e("OPTIMIZE TABLE `process`");			
						DI::lock()->release(Worker::LOCK_WORKER);
					}
					DI::lock()->release(Worker::LOCK_PROCESS);
				}
			}

			// Clear cache entries
			Worker::add(PRIORITY_LOW, 'ClearCache');

			DI::config()->set('system', 'last_cron_hourly', time());
		}

		// Ensure to have a .htaccess file.
		// this is a precaution for systems that update automatically
		$basepath = $a->getBasePath();
		if (!file_exists($basepath . '/.htaccess') && is_writable($basepath)) {
			copy($basepath . '/.htaccess-dist', $basepath . '/.htaccess');
		}

		// Poll contacts
		self::pollContacts();

		// Update contact information
		self::updatePublicContacts();

		Logger::log('cron: end');

		DI::config()->set('system', 'last_cron', time());

		return;
	}

	/**
	 * Optimize tables that are known to grow and shrink all the time
	 *
	 * @return void
	 */
	private static function optimizeTables()
	{
		Logger::info('Optimize start');

		DBA::e("OPTIMIZE TABLE `auth_codes`");
		DBA::e("OPTIMIZE TABLE `challenge`");
		DBA::e("OPTIMIZE TABLE `locks`");
		DBA::e("OPTIMIZE TABLE `profile_check`");
		DBA::e("OPTIMIZE TABLE `session`");
		DBA::e("OPTIMIZE TABLE `tokens`");

		DI::lock()->release('optimize_tables');
	}

	/**
	 * Checks for contacts that are about to be deleted and ensures that they are removed.
	 * This should be done automatically in the "remove" function. This here is a cleanup job.
	 */
	private static function checkdeletedContacts()
	{
		$contacts = DBA::select('contact', ['id'], ['deleted' => true]);
		while ($contact = DBA::fetch($contacts)) {
			Worker::add(PRIORITY_MEDIUM, 'RemoveContact', $contact['id']);
		}
		DBA::close($contacts);
	}

	/**
	 * Update public contacts
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function updatePublicContacts() {
		$count = 0;
		$last_updated = DateTimeFormat::utc('now - 1 week');
		$condition = ["`network` IN (?, ?, ?, ?) AND `uid` = ? AND NOT `self` AND `last-update` < ?",
			Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, 0, $last_updated];

		$oldest_date = '';
		$oldest_id = '';
		$contacts = DBA::select('contact', ['id', 'last-update'], $condition, ['limit' => 100, 'order' => ['last-update']]);
		while ($contact = DBA::fetch($contacts)) {
			if (empty($oldest_id)) {
				$oldest_id = $contact['id'];
				$oldest_date = $contact['last-update'];
			}
			Worker::add(PRIORITY_LOW, "UpdateContact", $contact['id']);
			++$count;
		}
		Logger::info('Initiated update for public contacts', ['interval' => $count, 'id' => $oldest_id, 'oldest' => $oldest_date]);
		DBA::close($contacts);
	}

	/**
	 * Poll contacts for unreceived messages
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function pollContacts() {
		$min_poll_interval = DI::config()->get('system', 'min_poll_interval');

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
			$t = $contact['last-update'];

			$poll_intervals = [$min_poll_interval . ' minute', '15 minute', '30 minute',
				'1 hour', '2 hour', '3 hour', '6 hour', '12 hour' ,'1 day', '1 week', '1 month'];

			if (empty($poll_intervals[$rating]) || (DateTimeFormat::utcNow() > DateTimeFormat::utc($t . ' + ' . $poll_intervals[$rating])))  {
				continue;
			}

			if ((($contact['network'] == Protocol::FEED) && ($contact['priority'] <= 3)) || ($contact['network'] == Protocol::MAIL)) {
				$priority = PRIORITY_MEDIUM;
			} elseif ($contact['archive']) {
				$priority = PRIORITY_NEGLIGIBLE;
			} else {
				$priority = PRIORITY_LOW;
			}

			Logger::log("Polling " . $contact["network"] . " " . $contact["id"] . " " . $contact['priority'] . " " . $contact["nick"] . " " . $contact["name"]);

			Worker::add(['priority' => $priority, 'dont_fork' => true, 'force_priority' => true], 'OnePoll', (int)$contact['id']);
		}
		DBA::close($contacts);
	}
}
