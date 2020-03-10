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

namespace Friendica\Model;

use Friendica\Core\Addon;
use Friendica\Database\DBA;
use Friendica\DI;

/**
 * Model interaction for the nodeinfo
 */
class Nodeinfo
{
	/**
	 * Updates the info about the current node
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function update()
	{
		$config = DI::config();
		$logger = DI::logger();

		// If the addon 'statistics_json' is enabled then disable it and activate nodeinfo.
		if (Addon::isEnabled('statistics_json')) {
			$config->set('system', 'nodeinfo', true);
			Addon::uninstall('statistics_json');
		}

		if (empty($config->get('system', 'nodeinfo'))) {
			return;
		}

		$userStats = User::getStatistics();

		$config->set('nodeinfo', 'total_users', $userStats['total_users']);
		$config->set('nodeinfo', 'active_users_halfyear', $userStats['active_users_halfyear']);
		$config->set('nodeinfo', 'active_users_monthly', $userStats['active_users_monthly']);

		$logger->debug('user statistics', $userStats);

		$items = DBA::p("SELECT COUNT(*) AS `total`, `gravity` FROM `item` WHERE `origin` AND NOT `deleted` AND `uid` != 0 AND `gravity` IN (?, ?) GROUP BY `gravity`",
			GRAVITY_PARENT, GRAVITY_COMMENT);
		while ($item = DBA::fetch($items)) {
			if ($item['gravity'] == GRAVITY_PARENT) {
				$config->set('nodeinfo', 'local_posts', $item['total']);
			} elseif ($item['gravity'] == GRAVITY_COMMENT) {
				$config->set('nodeinfo', 'local_comments', $item['total']);
			}
		}
		DBA::close($items);
	}
}
