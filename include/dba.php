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

use Friendica\Database\DBA;

/**
 * execute SQL query with printf style args - deprecated
 *
 * Please use the DBA:: functions instead:
 * DBA::select, DBA::exists, DBA::insert
 * DBA::delete, DBA::update, DBA::p, DBA::e
 *
 * @param $sql
 * @return array|bool Query array
 * @throws Exception
 * @deprecated
 */
function q($sql) {
	$args = func_get_args();
	unset($args[0]);

	if (!DBA::connected()) {
		return false;
	}

	$sql = DBA::cleanQuery($sql);
	$sql = DBA::anyValueFallback($sql);

	$stmt = @vsprintf($sql, $args);

	$ret = DBA::p($stmt);

	if (is_bool($ret)) {
		return $ret;
	}

	$columns = DBA::columnCount($ret);

	$data = DBA::toArray($ret);

	if ((count($data) == 0) && ($columns == 0)) {
		return true;
	}

	return $data;
}
