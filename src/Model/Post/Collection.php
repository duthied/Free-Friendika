<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
use BadMethodCallException;
use Friendica\Database\Database;

class Collection
{
	const FEATURED = 0;

	/**
	 * Add a post to a collection
	 *
	 * @param integer $uri_id
	 * @param integer $type
	 */
	public static function add(int $uri_id, int $type)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		DBA::insert('post-collection', ['uri-id' => $uri_id, 'type' => $type], Database::INSERT_IGNORE);
	}

	/**
	 * Remove a post from a collection
	 *
	 * @param integer $uri_id
	 * @param integer $type
	 */
	public static function remove(int $uri_id, int $type)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		DBA::delete('post-collection', ['uri-id' => $uri_id, 'type' => $type]);
	}

	/**
	 * Fetch collections for a given contact
	 *
	 * @param integer $cid
	 * @param [type] $type
	 * @param array $fields
	 * @return array
	 */
	public static function selectToArrayForContact(int $cid, int $type = self::FEATURED, array $fields = []) 
	{
		return DBA::selectToArray('collection-view', $fields, ['cid' => $cid, 'type' => $type]);
	}
}
