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

namespace Friendica\Database;

use Exception;
use Friendica\Core\Hook;
use Friendica\DI;

class View
{
	/**
	 * view definition loaded from static/dbview.config.php
	 *
	 * @var array
	 */
	private static $definition = [];

	/**
	 * Loads the database structure definition from the static/dbview.config.php file.
	 * On first pass, defines DB_UPDATE_VERSION constant.
	 *
	 * @see static/dbview.config.php
	 * @param boolean $with_addons_structure Whether to tack on addons additional tables
	 * @param string  $basePath              The base path of this application
	 * @return array
	 * @throws Exception
	 */
	public static function definition($basePath = '', $with_addons_structure = true)
	{
		if (!self::$definition) {
			if (empty($basePath)) {
				$basePath = DI::app()->getBasePath();
			}

			$filename = $basePath . '/static/dbview.config.php';

			if (!is_readable($filename)) {
				throw new Exception('Missing database view config file static/dbview.config.php');
			}

			$definition = require $filename;

			if (!$definition) {
				throw new Exception('Corrupted database view config file static/dbview.config.php');
			}

			self::$definition = $definition;
		} else {
			$definition = self::$definition;
		}

		if ($with_addons_structure) {
			Hook::callAll('dbview_definition', $definition);
		}

		return $definition;
	}

	public static function create(bool $verbose, bool $action)
	{
		$definition = self::definition();

		foreach ($definition as $name => $structure) {
			self::createview($name, $structure, $verbose, $action);
		}
	}

	public static function printStructure($basePath)
	{
		$database = self::definition($basePath, false);

		foreach ($database AS $name => $structure) {
			echo "--\n";
			echo "-- VIEW $name\n";
			echo "--\n";
			self::createView($name, $structure, true, false);

			echo "\n";
		}
	}

	private static function createview($name, $structure, $verbose, $action)
	{
		$r = true;

		$sql_rows = [];
		foreach ($structure["fields"] AS $fieldname => $origin) {
			if (is_string($origin)) {
				$sql_rows[] = $origin . " AS `" . DBA::escape($fieldname) . "`";
			} elseif (is_array($origin) && (sizeof($origin) == 2)) {
				$sql_rows[] = "`" . DBA::escape($origin[0]) . "`.`" . DBA::escape($origin[1]) . "` AS `" . DBA::escape($fieldname) . "`";
			}
		}

		$sql = sprintf("DROP VIEW IF EXISTS `%s`", DBA::escape($name));

		if ($verbose) {
			echo $sql . ";\n";
		}

		if ($action) {
			DBA::e($sql);
		}

		$sql = sprintf("CREATE VIEW `%s` AS SELECT \n\t", DBA::escape($name)) .
			implode(",\n\t", $sql_rows) . "\n\t" . $structure['query'];
	
		if ($verbose) {
			echo $sql . ";\n";
		}

		if ($action) {
			$r = DBA::e($sql);
		}

		return $r;
	}
}
