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
use Friendica\Model\Post;

class CleanItemUri
{
	/**
	 * Delete unused item-uri entries
	 */
	public static function execute()
	{
		// We have to avoid deleting newly created "item-uri" entries.
		// So we fetch a post that had been stored yesterday and only delete older ones.
		$item = Post::selectFirst(['uri-id'], ["`uid` = ? AND `received` < UTC_TIMESTAMP() - INTERVAL ? DAY", 0, 1],
			['order' => ['received' => true]]);
		if (empty($item['uri-id'])) {
			Logger::warning('No item with uri-id found - we better quit here');
			return;
		}
		Logger::notice('Start deleting orphaned URI-ID', ['last-id' => $item['uri-id']]);
		$ret = DBA::e("DELETE FROM `item-uri` WHERE `id` < ?
			AND NOT EXISTS(SELECT `uri-id` FROM `post-user` WHERE `uri-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `parent-uri-id` FROM `post-user` WHERE `parent-uri-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `thr-parent-id` FROM `post-user` WHERE `thr-parent-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `external-id` FROM `post-user` WHERE `external-id` = `item-uri`.`id`)", $item['uri-id']);
		Logger::notice('Orphaned URI-ID entries removed', ['result' => $ret, 'rows' => DBA::affectedRows()]);
	}
}
