<?php
/**
 * @file src/Worker/SetItemContentID.php
 * @brief This script sets the "icid" value in the item table if it couldn't set before.
 *
 * This script is started from mod/item.php to fix timing problems.
 */

namespace Friendica\Worker;

use Friendica\Model\Item;

class SetItemContentID {
	public static function execute($uri = '') {
		if (empty($uri)) {
			return;
		}

		Item::setICIDforURI($uri);
	}
}
