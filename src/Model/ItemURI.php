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

namespace Friendica\Model;

use Friendica\Database\DBA;

class ItemURI
{
	/**
	 * Insert an item-uri record and return its id
	 *
	 * @param array $fields Item-uri fields
	 *
	 * @return integer item-uri id
	 * @throws \Exception
	 */
	public static function insert($fields)
	{
		// If the URI gets too long we only take the first parts and hope for best
		$uri = substr($fields['uri'], 0, 255);

		if (!DBA::exists('item-uri', ['uri' => $uri])) {
			DBA::insert('item-uri', $fields, true);
		}

		$itemuri = DBA::selectFirst('item-uri', ['id'], ['uri' => $uri]);

		if (!DBA::isResult($itemuri)) {
			// This shouldn't happen
			return null;
		}

		return $itemuri['id'];
	}

	/**
	 * Searched for an id of a given uri. Adds it, if not existing yet.
	 *
	 * @param string $uri
	 *
	 * @return integer item-uri id
	 * @throws \Exception
	 */
	public static function getIdByURI($uri)
	{
		// If the URI gets too long we only take the first parts and hope for best
		$uri = substr($uri, 0, 255);

		$itemuri = DBA::selectFirst('item-uri', ['id'], ['uri' => $uri]);

		if (!DBA::isResult($itemuri)) {
			return self::insert(['uri' => $uri]);
		}

		return $itemuri['id'];
	}
}
