<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Core\Config\Util;

/**
 * Util to transform back the config array into a string
 */
class ConfigFileTransformer
{
	/**
	 * The public method to start the encoding
	 *
	 * @param array $data A full config array
	 *
	 * @return string The config stream, which can be saved
	 */
	public static function encode(array $data): string
	{
		// Add the typical header values
		$dataString = '<?php' . PHP_EOL . PHP_EOL;
		$dataString .= 'return ';

		$dataString .= static::extractArray($data);

		// the last array line, close it with a semicolon
		$dataString .= ";" . PHP_EOL;

		return $dataString;
	}

	/**
	 * Extracts an inner config array.
	 * Either as a Key => Value pair array or as an assoziative array
	 *
	 * @param array $config             The config array which should get extracted
	 * @param int   $level              The current level of recursion (necessary for tab-indentation calculation)
	 * @param bool  $inAssoziativeArray If true, the current array resides inside another assoziative array. Different rules may be applicable
	 *
	 * @return string The config string
	 */
	protected static function extractArray(array $config, int $level = 0, bool $inAssoziativeArray = false): string
	{
		if (array_values($config) === $config) {
			return self::extractAssoziativeArray($config, $level, $inAssoziativeArray);
		} else {
			return self::extractKeyValueArray($config, $level, $inAssoziativeArray);
		}
	}

	/**
	 * Extracts a key-value array and save it into a string
	 * output:
	 * [
	 *    'key' => value,
	 *    'key' => value,
	 *    ...
	 * ]
	 *
	 * @param array $config             The key-value array
	 * @param int   $level              The current level of recursion (necessary for tab-indentation calculation)
	 * @param bool  $inAssoziativeArray If true, the current array resides inside another assoziative array. Different rules may be applicable
	 *
	 * @return string The config string
	 */
	protected static function extractKeyValueArray(array $config, int $level = 0, bool $inAssoziativeArray = false): string
	{
		$string = '';

		// Because we're in an assoziative array, we have to add a line-break first
		if ($inAssoziativeArray) {
			$string .= PHP_EOL . str_repeat("\t", $level);
		}

		// Add a typical Line break for a taxative list of key-value pairs
		$string .= '[' . PHP_EOL;

		foreach ($config as $configKey => $configValue) {
			$string .= str_repeat("\t", $level + 1) .
					   "'$configKey' => " .
					   static::transformConfigValue($configValue, $level) .
					   ',' . PHP_EOL;
		}

		$string .= str_repeat("\t", $level) . ']';

		return $string;
	}

	/**
	 * Extracts an assoziative array and save it into a string
	 * output1 - simple:
	 * [ value, value, value ]
	 *
	 * output2 - complex:
	 * [
	 *    [ value, value, value ],
	 *    value,
	 *    [
	 *       key => value,
	 *       key => value,
	 *    ],
	 * ]
	 *
	 * @param array $config             The assoziative array
	 * @param int   $level              The current level of recursion (necessary for tab-indentation calculation)
	 * @param bool  $inAssoziativeArray If true, the current array resides inside another assoziative array. Different rules may be applicable
	 *
	 * @return string The config string
	 */
	protected static function extractAssoziativeArray(array $config, int $level = 0, bool $inAssoziativeArray = false): string
	{
		$string = '[';

		$countConfigValues = count($config);
		// multiline defines, if each entry uses a new line
		$multiline = false;

		// Search if any value is an array, because then other formatting rules are applicable
		foreach ($config as $item) {
			if (is_array($item)) {
				$multiline = true;
				break;
			}
		}

		for ($i = 0; $i < $countConfigValues; $i++) {
			$isArray = is_array($config[$i]);

			/**
			 * In case this is an array in an array, directly extract this array again and continue
			 * Skip any other logic since this isn't applicable for an array in an array
			 */
			if ($isArray) {
				$string   .= PHP_EOL . str_repeat("\t", $level + 1);
				$string   .= static::extractArray($config[$i], $level + 1, $inAssoziativeArray) . ',';
				continue;
			}

			if ($multiline) {
				$string .= PHP_EOL . str_repeat("\t", $level + 1);
			}

			$string .= static::transformConfigValue($config[$i], $level, true);

			// add trailing commas or whitespaces for certain config entries
			if (($i < ($countConfigValues - 1))) {
				$string .= ',';
				if (!$multiline) {
					$string .= ' ';
				}
			}
		}

		// Add a new line for the last bracket as well
		if ($multiline) {
			$string .= PHP_EOL . str_repeat("\t", $level);
		}

		$string .= ']';

		return $string;
	}

	/**
	 * Transforms one config value and returns the corresponding text-representation
	 *
	 * @param mixed $value              Any value to transform
	 * @param int   $level              The current level of recursion (necessary for tab-indentation calculation)
	 * @param bool  $inAssoziativeArray If true, the current array resides inside another assoziative array. Different rules may be applicable
	 *
	 * @return string
	 */
	protected static function transformConfigValue($value, int $level = 0, bool $inAssoziativeArray = false): string
	{
		switch (gettype($value)) {
			case "boolean":
				return ($value ? 'true' : 'false');
			case "integer":
			case "double":
				return $value;
			case "string":
				return sprintf('\'%s\'', addcslashes($value, '\'\\'));
			case "array":
				return static::extractArray($value, ++$level, $inAssoziativeArray);
			case "NULL":
				return "null";
			case "object":
			case "resource":
			case "resource (closed)":
				throw new \InvalidArgumentException(sprintf('%s in configs are not supported yet.', gettype($value)));
			case "unknown type":
				throw new \InvalidArgumentException(sprintf('%s is an unknown value', $value));
			default:
				throw new \InvalidArgumentException(sprintf('%s is currently unsupported', $value));
		}
	}
}
