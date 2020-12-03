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
use Friendica\Database\DBA;

class UpdateGServers
{
	/**
	 * Updates up to 100 servers
	 */
	public static function execute()
	{
		$updating = Worker::countWorkersByCommand('UpdateGServer');
		$limit = 100 - $updating;
		if ($limit <= 0) {
			Logger::info('The number of currently running jobs exceed the limit');
			return;
		}

		$outdated = DBA::count('gserver', ["`next_contact` < UTC_TIMESTAMP()"]);
		$total = DBA::count('gserver');
		Logger::info('Server status', ['total' => $total, 'outdated' => $outdated, 'updating' => $limit]);

		$gservers = DBA::select('gserver', ['url'], ["`next_contact` < UTC_TIMESTAMP()"], ['limit' => $limit]);
		if (!DBA::isResult($gservers)) {
			return;
		}

		$count = 0;
		while ($gserver = DBA::fetch($gservers)) {
			Worker::add(PRIORITY_LOW, 'UpdateGServer', $gserver['url'], false, true);
			$count++;
		}
		DBA::close($gservers);
		Logger::info('Updated servers', ['count' => $count]);
	}
}
