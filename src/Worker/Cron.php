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
use Friendica\DI;
use Friendica\Model\Tag;

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

		Logger::notice('start');

		// Ensure to have a .htaccess file.
		// this is a precaution for systems that update automatically
		$basepath = $a->getBasePath();
		if (!file_exists($basepath . '/.htaccess') && is_writable($basepath)) {
			copy($basepath . '/.htaccess-dist', $basepath . '/.htaccess');
		}

		// Fork the cron jobs in separate parts to avoid problems when one of them is crashing
		Hook::fork($a->queue['priority'], 'cron');

		// Poll contacts
		Worker::add(PRIORITY_MEDIUM, 'PollContacts');

		// Update contact information
		Worker::add(PRIORITY_LOW, 'UpdatePublicContacts');		

		// run the process to update server directories in the background
		Worker::add(PRIORITY_LOW, 'UpdateServerDirectories');

		// Expire and remove user entries
		Worker::add(PRIORITY_MEDIUM, 'ExpireAndRemoveUsers');

		// Call possible post update functions
		Worker::add(PRIORITY_LOW, 'PostUpdate');

		// Repair entries in the database
		Worker::add(PRIORITY_LOW, 'RepairDatabase');

		// Hourly cron calls
		if (DI::config()->get('system', 'last_cron_hourly', 0) + 3600 < time()) {

			// Update trending tags cache for the community page
			Tag::setLocalTrendingHashtags(24, 20);
			Tag::setGlobalTrendingHashtags(24, 20);

			// Search for new contacts in the directory
			if (DI::config()->get('system', 'synchronize_directory')) {
				Worker::add(PRIORITY_LOW, 'PullDirectory');
			}

			// Delete all done workerqueue entries			
			Worker::add(PRIORITY_LOW, 'CleanWorkerQueue');

			// Clear cache entries
			Worker::add(PRIORITY_LOW, 'ClearCache');

			DI::config()->set('system', 'last_cron_hourly', time());
		}

		// Daily cron calls
		if (DI::config()->get('system', 'last_cron_daily', 0) + 86400 < time()) {

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

			Worker::add(PRIORITY_LOW, 'CheckDeletedContacts');

			if (DI::config()->get('system', 'optimize_tables')) {
				Worker::add(PRIORITY_LOW, 'OptimizeTables');
			}
	
			DI::config()->set('system', 'last_cron_daily', time());
		}

		Logger::notice('end');

		DI::config()->set('system', 'last_cron', time());
	}
}
