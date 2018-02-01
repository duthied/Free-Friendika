<?php
/**
 * @file include/like.php
 */

use Friendica\Model\Item;

function do_like($item_id, $verb) {
	Item::performLike($item_id, $verb);
}
