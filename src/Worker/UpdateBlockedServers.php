<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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
use Friendica\Model\GServer;
use Friendica\Util\Network;

class UpdateBlockedServers
{
	/**
	 * Updates the server blocked status
	 */
	public static function execute()
	{
		Logger::info('Update blocked servers - start');
		$gservers = DBA::select('gserver', ['id', 'url', 'blocked']);
		$changed  = 0;
		$unchanged = 0;
		while ($gserver = DBA::fetch($gservers)) {
			$blocked = Network::isUrlBlocked($gserver['url']);
			if (!is_null($gserver['blocked']) && ($blocked == $gserver['blocked'])) {
				$unchanged++;
				continue;
			}

			if ($blocked) {
				GServer::setBlockedById($gserver['id']);
			} else {
				GServer::setUnblockedById($gserver['id']);
			}
			$changed++;
		}
		DBA::close($gservers);
		Logger::info('Update blocked servers - done', ['changed' => $changed, 'unchanged' => $unchanged]);

		if (DI::config()->get('system', 'delete-blocked-servers')) {
			Logger::info('Delete blocked servers - start');
			$ret = DBA::delete('gserver', ["`blocked` AND NOT EXISTS(SELECT `gsid` FROM `inbox-status` WHERE `gsid` = `gserver`.`id`) AND NOT EXISTS(SELECT `gsid` FROM `contact` WHERE gsid= `gserver`.`id`) AND NOT EXISTS(SELECT `gsid` FROM `apcontact` WHERE `gsid` = `gserver`.`id`) AND NOT EXISTS(SELECT `gsid` FROM `delivery-queue` WHERE `gsid` = `gserver`.`id`) AND NOT EXISTS(SELECT `gsid` FROM `diaspora-contact` WHERE `gsid` = `gserver`.`id`) AND NOT EXISTS(SELECT `gserver-id` FROM `gserver-tag` WHERE `gserver-id` = `gserver`.`id`)"]);
			Logger::info('Delete blocked servers - done', ['ret' => $ret, 'rows' => DBA::affectedRows()]);
		}
	}
}
