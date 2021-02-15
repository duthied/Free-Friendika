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
use Friendica\DI;
use Friendica\Model\Item;

class ExpirePosts
{
	/**
	 * Delete old post entries
	 */
	public static function execute()
	{
		$expire_days = DI::config()->get('system', 'dbclean-expire-days');
		$expire_days_unclaimed = DI::config()->get('system', 'dbclean-expire-unclaimed');
		if (empty($expire_days_unclaimed)) {
			$expire_days_unclaimed = $expire_days;
		}

		$limit = DI::config()->get('system', 'dbclean-expire-limit');
		if (empty($limit)) {
			return;
		}

		if (!empty($expire_days)) {
			do {
				Logger::notice('Start deleting expired threads', ['expiry_days' => $expire_days]);
				$ret = DBA::e("DELETE FROM `item-uri` WHERE `id` IN
					(SELECT `uri-id` FROM `post-thread` WHERE `received` < UTC_TIMESTAMP() - INTERVAL ? DAY
						AND NOT `uri-id` IN (SELECT `uri-id` FROM `post-thread-user`
							WHERE (`mention` OR `starred` OR `wall` OR `pinned`) AND `uri-id` = `post-thread`.`uri-id`)
						AND NOT `uri-id` IN (SELECT `uri-id` FROM `post-category`
							WHERE `uri-id` = `post-thread`.`uri-id`)
						AND NOT `uri-id` IN (SELECT `uri-id` FROM `post-media`
							WHERE `uri-id` = `post-thread`.`uri-id`)
						AND NOT `uri-id` IN (SELECT `parent-uri-id` FROM `post-user` INNER JOIN `contact` ON `contact`.`id` = `contact-id` AND `notify_new_posts`
							WHERE `parent-uri-id` = `post-thread`.`uri-id`)
						AND NOT `uri-id` IN (SELECT `parent-uri-id` FROM `post-user`
							WHERE (`origin` OR `event-id` != 0 OR `post-type` = ?) AND `parent-uri-id` = `post-thread`.`uri-id`)
						AND NOT `uri-id` IN (SELECT `uri-id` FROM `post-content`
							WHERE `resource-id` != 0 AND `uri-id` = `post-thread`.`uri-id`))
					ORDER BY `id` LIMIT ?", $expire_days, Item::PT_PERSONAL_NOTE, $limit);

				$rows = DBA::affectedRows();
				Logger::notice('Deleted expired threads', ['result' => $ret, 'rows' => $rows]);

				if (!Worker::isInMaintenanceWindow()) {
					Logger::notice('We are outside of the maintenance window, quitting');
					return;
				}
			} while ($rows >= $limit);
		}

		if (!empty($expire_days_unclaimed)) {
			do {
				Logger::notice('Start deleting unclaimed public items', ['expiry_days' => $expire_days_unclaimed]);
				$ret = DBA::e("DELETE FROM `item-uri` WHERE `id` IN
					(SELECT `uri-id` FROM `post-user` WHERE `gravity` = ? AND `uid` = ? AND `received` < UTC_TIMESTAMP() - INTERVAL ? DAY
						AND NOT `uri-id` IN (SELECT `parent-uri-id` FROM `post-user` AS `i` WHERE `i`.`uid` != ?
							AND `i`.`parent-uri-id` = `post-user`.`uri-id`)
						AND NOT `uri-id` IN (SELECT `parent-uri-id` FROM `post-user` AS `i` WHERE `i`.`uid` = ?
							AND `i`.`parent-uri-id` = `post-user`.`uri-id` AND `i`.`received` > UTC_TIMESTAMP() - INTERVAL ? DAY))
					ORDER BY `id` LIMIT ?",
					GRAVITY_PARENT, 0, $expire_days_unclaimed, 0, 0, $expire_days_unclaimed, $limit);

				$rows = DBA::affectedRows();
				Logger::notice('Deleted unclaimed public items', ['result' => $ret, 'rows' => $rows]);

				if (!Worker::isInMaintenanceWindow()) {
					Logger::notice('We are outside of the maintenance window, quitting');
					return;
				}
			} while ($rows >= $limit);
		}
	}
}
