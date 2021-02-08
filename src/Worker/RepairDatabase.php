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

use Friendica\Database\DBA;
use Friendica\Model\ItemURI;

/**
 * Do some repairs in database entries
 * @todo This class can be deleted without replacement when the item table is removed
 */
class RepairDatabase
{
	public static function execute()
	{
		// Ensure that there are no "uri-id", "parent-uri-id" or "thr-parent-id" fields that are NULL
		$items = DBA::select('item', ['id', 'uri', 'guid'], ["`uri-id` IS NULL"]);
		while ($item = DBA::fetch($items)) {
			$uriid = ItemURI::insert(['uri' => $item['uri'], 'guid' => $item['guid']]);
			DBA::update('item', ['uri-id' => $uriid], ['id' => $item['id']]);
		}
		DBA::close($items);

		$items = DBA::select('item', ['id', 'parent-uri'], ["`parent-uri-id` IS NULL"]);
		while ($item = DBA::fetch($items)) {
			$uriid = ItemURI::getIdByURI($item['parent-uri']);
			DBA::update('item', ['parent-uri-id' => $uriid], ['id' => $item['id']]);
		}
		DBA::close($items);

		$items = DBA::select('item', ['id', 'thr-parent'], ["`thr-parent-id` IS NULL"]);
		while ($item = DBA::fetch($items)) {
			$uriid = ItemURI::getIdByURI($item['thr-parent']);
			DBA::update('item', ['thr-parent-id' => $uriid], ['id' => $item['id']]);
		}
		DBA::close($items);

		// Ensure that all uri-id are set correctly
		DBA::e("UPDATE `item` INNER JOIN `item-uri` ON `item-uri`.`uri` = `item`.`uri`
			SET `uri-id` = `item-uri`.`id` WHERE `item`.`uri-id` != `item-uri`.`id` AND `item`.`uri` != ?", '');
		DBA::e("UPDATE `item` INNER JOIN `item-uri` ON `item-uri`.`uri` = `item`.`parent-uri`
			SET `parent-uri-id` = `item-uri`.`id` WHERE `item`.`parent-uri-id` != `item-uri`.`id` AND `item`.`parent-uri` != ?", '');
		DBA::e("UPDATE `item` INNER JOIN `item-uri` ON `item-uri`.`uri` = `item`.`thr-parent`
			SET `thr-parent-id` = `item-uri`.`id` WHERE `item`.`thr-parent-id` != `item-uri`.`id` AND `item`.`thr-parent` != ?", '');

		// Delete orphaned data from notify table.
		DBA::e("DELETE FROM `notify` WHERE NOT `type` IN (1, 2, 16, 32, 512) AND NOT `iid` IN (SELECT `id` FROM `item`)");
	}
}
