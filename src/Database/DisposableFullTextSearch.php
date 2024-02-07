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

namespace Friendica\Database;

/**
 * Full-text search on a haystack string that isn't present in the database.
 * The haystack is inserted in a temporary table with a FULLTEXT index, then any number of
 * matches can be performed on it before the row is deleted when the class instance is destroyed,
 * either manually or at the end of the script at the latest.
 */
class DisposableFullTextSearch
{
	private Database $db;
	/** @var int Unique identifier of the haystack in the database. */
	private int $identifier;

	public function __construct(Database $database, string $haystack)
	{
		$this->db = $database;

		// Unique identifier generation. Two DisposableFullTextSearch object should never have the same as the first object destruction
		// would delete both check-full-text-search rows before the second object destruction is called, leading to unexpected behavior.
		do {
			// Maximum value is indicated by the INT UNSIGNED type of the check-full-text-search.pid field
			$this->identifier = random_int(0, pow(2, 32) - 1);
		} while ($this->db->exists('check-full-text-search', ['pid' => $this->identifier]));

		// If the `exists()` call fails and return false because the database is unavailable, the `insert()` call will likely fail as well, which means
		// all subsequent calls to `match()` will return false because the haystack won't have been inserted.
		// However, at this point there may be bigger problems to worry about.
		$this->db->insert('check-full-text-search', ['pid' => $this->identifier, 'searchtext' => $haystack]);
	}

	public function __destruct()
	{
		$this->db->delete('check-full-text-search', ['pid' => $this->identifier]);
	}

	/**
	 * @param string $needle Boolean mode search string
	 * @return bool
	 * @throws \Exception
	 */
	public function match(string $needle): bool
	{
		return $this->db->exists('check-full-text-search', ["`pid` = ? AND MATCH (`searchtext`) AGAINST (? IN BOOLEAN MODE)", $this->identifier, $needle]);
	}
}
