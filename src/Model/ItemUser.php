<?php

namespace Friendica\Model;

use Exception;
use Friendica\Database\DBA;

/**
 * Model for user specific operations of items
 */
class ItemUser
{
	/**
	 * Returns fields of the user for an item
	 *
	 * @param int   $id     The item id
	 * @param array $fields The fields, which should get returned
	 *
	 * @return array|bool
	 * @throws Exception In case of a DB-failure
	 */
	public static function getUserForItemId($id, array $fields = [])
	{
		$item = DBA::selectFirst('item', ['uid'], ['id' => $id]);
		if (empty($item)) {
			return false;
		}

		$user = DBA::selectFirst('user', $fields, ['uid' => $item['uid']]);
		if (!empty($user)) {
			return $user;
		} else {
			return false;
		}
	}
}
