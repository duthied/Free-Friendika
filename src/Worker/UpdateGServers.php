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
use Friendica\Model\GServer;

class UpdateGServers
{
	/**
	 * Updates the first 250 servers
	 */
	public static function execute()
	{
		$gservers = DBA::p("SELECT `url`, `created`, `last_failure`, `last_contact` FROM `gserver` ORDER BY rand()");
		if (!DBA::isResult($gservers)) {
			return;
		}

		$updated = 0;

		while ($gserver = DBA::fetch($gservers)) {
			if (!GServer::updateNeeded($gserver['created'], '', $gserver['last_failure'], $gserver['last_contact'])) {
				continue;
			}
			Logger::info('Update server status', ['server' => $gserver['url']]);

			Worker::add(PRIORITY_LOW, 'UpdateGServer', $gserver['url']);

			if (++$updated > 250) {
				return;
			}
		}
	}
}
