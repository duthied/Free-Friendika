<?php

/**
 * @file src/Model/Item.php
 */

namespace Friendica\Model;

use Friendica\Core\Worker;
use dba;

require_once 'include/tags.php';
require_once 'include/files.php';
require_once 'include/threads.php';

class Item
{
	/**
	 * @brief Update existing item entries
	 *
	 * @param array $fields The fields that are to be changed
	 * @param array $condition The condition for finding the item entries
	 *
	 * In the future we may have to change permissions as well.
	 * Then we had to add the user id as third parameter.
	 *
	 * A return value of "0" doesn't mean an error - but that 0 rows had been changed.
	 *
	 * @return integer|boolean number of affected rows - or "false" if there was an error
	 */
	public static function update(array $fields, array $condition)
	{
		if (empty($condition) || empty($fields)) {
			return false;
		}

		$success = dba::update('item', $fields, $condition);

		if (!$success) {
			return false;
		}

		$rows = dba::affected_rows();

		// We cannot simply expand the condition to check for origin entries
		// The condition needn't to be a simple array but could be a complex condition.
		$items = dba::select('item', ['id', 'origin'], $condition);
		while ($item = dba::fetch($items)) {
			// We only need to notfiy others when it is an original entry from us
			if (!$item['origin']) {
				continue;
			}

			create_tags_from_item($item['id']);
			create_files_from_item($item['id']);
			update_thread($item['id']);

			Worker::add(PRIORITY_HIGH, "Notifier", 'edit_post', $item['id']);
		}

		return $rows;
	}
}
