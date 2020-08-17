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
use Friendica\Core\Protocol;
use Friendica\Model\Item;

/**
 * Removes orphaned data from deleted contacts
 */
class RemoveContact {
	public static function execute($id) {

		// Only delete if the contact is to be deleted
		$contact = DBA::selectFirst('contact', ['uid'], ['deleted' => true]);
		if (!DBA::isResult($contact)) {
			return;
		}

		// Now we delete the contact and all depending tables
		$condition = ['uid' => $contact['uid'], 'contact-id' => $id];
		do {
			$items = Item::select(['id', 'guid'], $condition, ['limit' => 100]);
			while ($item = Item::fetch($items)) {
				Logger::info('Delete removed contact item', ['id' => $item['id'], 'guid' => $item['guid']]);
				DBA::delete('item', ['id' => $item['id']]);
			}
			DBA::close($items);
		} while (Item::exists($condition));

		DBA::delete('contact', ['id' => $id]);
	}
}
