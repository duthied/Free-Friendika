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
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\DateTimeFormat;

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

		if (!empty($expire_days)) {
			Logger::notice('Start deleting expired threads', ['expiry_days' => $expire_days, 'count' => DBA::count('item')]);
			$ret = DBA::e("DELETE FROM `item-uri` WHERE `id` IN
				(SELECT `uri-id` FROM `thread`
				INNER JOIN `contact` ON `id` = `contact-id` AND NOT `notify_new_posts`
				WHERE `received` < UTC_TIMESTAMP() - INTERVAL ? DAY
					AND NOT `mention` AND NOT `starred` AND NOT `wall` AND NOT `origin`
					AND `thread`.`uid` != 0 AND NOT `iid` IN (SELECT `parent` FROM `item`
						WHERE (`item`.`starred` OR (`item`.`resource-id` != '')
							OR (`item`.`event-id` != '') OR (`item`.`attach` != '')
							OR `item`.`wall` OR `item`.`origin`
							OR `uri-id` IN (SELECT `uri-id` FROM `post-category`
								WHERE `uri-id` = `item`.`uri-id`))
							AND `item`.`parent` = `thread`.`iid`))", $expire_days);

			Logger::notice('Deleted expired threads', ['result' => $ret, 'rows' => DBA::affectedRows(), 'count' => DBA::count('item')]);
		}

		if (!empty($expire_days_unclaimed)) {
			$expiry_date = DateTimeFormat::utc('now - ' . $expire_days_unclaimed . ' days', DateTimeFormat::MYSQL);

			Logger::notice('Start deleting unclaimed public items', ['expiry_days' => $expire_days_unclaimed, 'expired' => $expiry_date, 'count' => DBA::count('item')]);
			$ret = DBA::e("DELETE FROM `item-uri` WHERE `id` IN
				(SELECT `uri-id` FROM `item` WHERE `gravity` = ? AND `uid` = ? AND `received` < ?
					AND NOT `uri-id` IN (SELECT `parent-uri-id` FROM `item` WHERE `uid` != ?)
					AND NOT `uri-id` IN (SELECT `parent-uri-id` FROM `item` WHERE `uid` = ? AND `received` > ?))",
				GRAVITY_PARENT, 0, $expiry_date, 0, 0, $expiry_date);

			Logger::notice('Deleted unclaimed public items', ['result' => $ret, 'rows' => DBA::affectedRows(), 'count' => DBA::count('item')]);
		}
	}
}
