<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Post;

class SearchIndex
{
	/**
	 * Insert a post-searchindex entry
	 *
	 * @param int $uri_id
	 * @param string $network
	 * @param int $private
	 */
	public static function insert(int $uri_id, string $network, int $private)
	{
		$search = [
			'uri-id' => $uri_id,
			'network' => $network,
			'private' => $private,
			'searchtext' => Post\Engagement::getSearchTextForUriId($uri_id),
		];
		return DBA::insert('post-searchindex', $search, Database::INSERT_UPDATE);
	}

	/**
	 * update a post-searchindex entry
	 *
	 * @param int $uri_id
	 */
	public static function update(int $uri_id)
	{
		$searchtext = Post\Engagement::getSearchTextForUriId($uri_id, true);
		return DBA::update('post-searchindex', ['searchtext' => $searchtext], ['uri-id' => $uri_id]);
	}
}
