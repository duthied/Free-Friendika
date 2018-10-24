<?php
/**
 * @file src/Worker/RemoveContact.php
 * @brief Removes orphaned data from deleted contacts
 */
namespace Friendica\Worker;

use Friendica\Database\DBA;
use Friendica\Core\Protocol;
use Friendica\Model\Item;

require_once 'include/dba.php';

class RemoveContact {
	public static function execute($id) {

		// Only delete if the contact is to be deleted
		$condition = ['network' => Protocol::PHANTOM, 'id' => $id];
		$r = DBA::exists('contact', $condition);
		if (!DBA::isResult($r)) {
			return;
		}

		// Now we delete the contact and all depending tables
		$condition = ['contact-id' => $id];
		do {
			$items = Item::select(['id'], $condition, ['limit' => 100]);
			while ($item = Item::fetch($items)) {
				DBA::delete('item', ['id' => $item['id']]);
			}
			DBA::close($items);
		} while (Item::exists($condition));

		DBA::delete('contact', ['id' => $id]);
	}
}
