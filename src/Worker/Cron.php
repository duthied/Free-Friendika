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

use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
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
				Logger::notice('cron intervall not reached');
				return;
			}
		}

		Logger::notice('cron: start');

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

			Worker::add(PRIORITY_LOW, 'ExpireConversations');

			Worker::add(PRIORITY_LOW, 'CleanItemUri');

			// check upstream version?
			Worker::add(PRIORITY_LOW, 'CheckVersion');

			Worker::add(PRIORITY_LOW, 'CheckdeletedContacts');

			if (DI::config()->get('system', 'optimize_tables')) {
				Worker::add(PRIORITY_LOW, 'OptimizeTables');
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
		Worker::add(PRIORITY_HIGH, 'PollContacts');

		// Update contact information
		Worker::add(PRIORITY_LOW, 'UpdatePublicContacts');		

		Logger::notice('cron: end');

		DI::config()->set('system', 'last_cron', time());

		return;
	}
}
