<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

class UpdateGServers
{
	/**
	 * Updates a defined number of servers
	 */
	public static function execute()
	{
		$update_limit = DI::config()->get('system', 'gserver_update_limit');
		if (empty($update_limit)) {
			return;
		}

		$updating = Worker::countWorkersByCommand('UpdateGServer');
		$limit = $update_limit - $updating;
		if ($limit <= 0) {
			Logger::info('The number of currently running jobs exceed the limit');
			return;
		}

		$total = DBA::count('gserver');
		$condition = ["NOT `blocked` AND `next_contact` < ? AND (`nurl` != ? OR `url` != ?)",  DateTimeFormat::utcNow(), '', ''];
		$outdated = DBA::count('gserver', $condition);
		Logger::info('Server status', ['total' => $total, 'outdated' => $outdated, 'updating' => $limit]);

		$gservers = DBA::select('gserver', ['id', 'url', 'nurl', 'failed', 'created', 'last_contact'], $condition, ['limit' => $limit]);
		if (!DBA::isResult($gservers)) {
			return;
		}

		$count = 0;
		while ($gserver = DBA::fetch($gservers)) {
			if (DI::config()->get('system', 'update_active_contacts') && !Contact::exists(['gsid' => $gserver['id'], 'local-data' => true])) {
				$next_update = GServer::getNextUpdateDate(!$gserver['failed'], $gserver['created'], $gserver['last_contact']);
				Logger::debug('Skip server without contacts with local data', ['url' => $gserver['url'], 'failed' => $gserver['failed'], 'next_update' => $next_update]);
				GServer::update(['next_contact' => $next_update], ['nurl' => $gserver['nurl']]);
				continue;
			}

			// Sometimes the "nurl" and "url" doesn't seem to fit, see https://forum.friendi.ca/display/ec054ce7-155f-c94d-6159-f50372664245
			// There are duplicated "url" but not "nurl". So we check both addresses instead of just overwriting them,
			// since that would mean loosing data.
			if (!empty($gserver['url'])) {
				if (UpdateGServer::add(Worker::PRIORITY_LOW, $gserver['url'])) {
					$count++;
				}
			}
			if (!empty($gserver['nurl']) && ($gserver['nurl'] != Strings::normaliseLink($gserver['url']))) {
				if (UpdateGServer::add(Worker::PRIORITY_LOW, $gserver['nurl'])) {
					$count++;
				}
			}
			Worker::coolDown();
		}
		DBA::close($gservers);
		Logger::info('Updated servers', ['count' => $count]);
	}
}
