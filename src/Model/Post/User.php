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

namespace Friendica\Model\Post;

use Friendica\Database\DBA;
use \BadMethodCallException;
use Friendica\Core\Logger;
use Friendica\Database\DBStructure;

class User
{
	/**
	 * Insert a new URI user entry
	 *
	 * @param integer $uri_id
	 * @param integer $uid
	 * @param array   $fields
	 * @return bool
	 * @throws \Exception
	 */
	public static function insert(int $uri_id, int $uid, array $data = [])
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		if (DBA::exists('post-user', ['uri-id' => $uri_id, 'uid' => $uid])) {
			return false;
		}

		$fields = DBStructure::getFieldsForTable('post-user', $data);

		// Additionally assign the key fields
		$fields['uri-id'] = $uri_id;
		$fields['uid'] = $uid;

		// Public posts are always seen
		if ($uid == 0) {
			$fields['unseen'] = false;
		}

		return DBA::insert('post-user', $fields);
	}

	/**
	 * Update a URI user entry
	 *
	 * @param integer $uri_id
	 * @param integer $uid
	 * @param array   $fields
	 * @return bool
	 * @throws \Exception
	 */
	public static function update(int $uri_id, int $uid, array $data = [])
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields = DBStructure::getFieldsForTable('post-user', $data);

		// Remove the key fields
		unset($fields['uri-id']);
		unset($fields['uid']);

		if (empty($fields)) {
			return true;
		}

Logger::info('Blubb-Update', ['uri-id' => $uri_id, 'uid' => $uid, 'fields' => $fields]);
		return DBA::update('post-user', $fields, ['uri-id' => $uri_id, 'uid' => $uid], true);
	}
}
