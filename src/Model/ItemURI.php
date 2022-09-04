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

namespace Friendica\Model;

use Friendica\Database\Database;
use Friendica\Database\DBA;

class ItemURI
{
	/**
	 * Insert an item-uri record and return its id
	 *
	 * @param array $fields Item-uri fields
	 * @return int|null item-uri id
	 * @throws \Exception
	 */
	public static function insert(array $fields)
	{
		if (!DBA::exists('item-uri', ['uri' => $fields['uri']])) {
			DBA::insert('item-uri', $fields, Database::INSERT_UPDATE);
		}

		$itemuri = DBA::selectFirst('item-uri', ['id', 'guid'], ['uri' => $fields['uri']]);

		if (!DBA::isResult($itemuri)) {
			// This shouldn't happen
			return null;
		}

		if (empty($itemuri['guid']) && !empty($fields['guid'])) {
			DBA::update('item-uri', ['guid' => $fields['guid']], ['id' => $itemuri['id']]);
		}

		return $itemuri['id'];
	}

	/**
	 * Searched for an id of a given uri. Adds it, if not existing yet.
	 *
	 * @param string $uri
	 * @param bool   $insert
	 *
	 * @return integer item-uri id
	 *
	 * @throws \Exception
	 */
	public static function getIdByURI(string $uri, bool $insert = true): int
	{
		if (empty($uri)) {
			return 0;
		}

		$itemuri = DBA::selectFirst('item-uri', ['id'], ['uri' => $uri]);

		if (!DBA::isResult($itemuri) && $insert) {
			return self::insert(['uri' => $uri]);
		}

		return $itemuri['id'] ?? 0;
	}

	/**
	 * Searched for an id of a given guid.
	 *
	 * @param string $guid
	 * @return integer item-uri id
	 * @throws \Exception
	 */
	public static function getIdByGUID(string $guid): int
	{
		$itemuri = DBA::selectFirst('item-uri', ['id'], ['guid' => $guid]);

		if (!DBA::isResult($itemuri)) {
			return 0;
		}

		return $itemuri['id'];
	}
}
