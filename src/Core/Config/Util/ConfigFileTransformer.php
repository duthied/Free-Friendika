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
	 * This method takes an array of config values and applies some standard rules for formatting on it
	 *
	 * Beware that the applied rules follow some basic formatting principles for node.config.php
	 * and doesn't support any custom formatting rules.
	 *
	 * f.e. associative array and list formatting are very complex with newlines and indentations, thus there are
	 * three hardcoded types of formatting for them.
	 *
	 * a negative example, what's NOT working:
	 * key => [ value1, [inner_value1, inner_value2], value2]
	 * Since this isn't necessary for config values, there's no further logic handling such complex-list-in-list scenarios
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
	 * Either as an associative array or as a list
	 *
	 * @param array $config The config array which should get extracted
	 * @param int   $level  The current level of recursion (necessary for tab-indentation calculation)
	 * @param bool  $inList If true, the current array resides inside another list. Different rules may be applicable
	 *
	 * @return string The config string
	 */
	protected static function extractArray(array $config, int $level = 0, bool $inList = false): string
	{
		if (array_values($config) === $config) {
			return self::extractList($config, $level, $inList);
		} else {
			return self::extractAssociativeArray($config, $level, $inList);
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
	 * @param array $config The associative/key-value array
	 * @param int   $level  The current level of recursion (necessary for tab-indentation calculation)
	 * @param bool  $inList If true, the current array resides inside another list. Different rules may be applicable
	 *
	 * @return string The config string
	 */
	protected static function extractAssociativeArray(array $config, int $level = 0, bool $inList = false): string
	{
		$string = '';

		// Because we're in a list, we have to add a line-break first
		if ($inList) {
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
	 * Extracts a list and save it into a string
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
	 * @param array $config The list
	 * @param int   $level  The current level of recursion (necessary for tab-indentation calculation)
	 * @param bool  $inList If true, the current array resides inside another list. Different rules may be applicable
	 *
	 * @return string The config string
	 */
	protected static function extractList(array $config, int $level = 0, bool $inList = false): string
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
				$string .= PHP_EOL . str_repeat("\t", $level + 1);
				$string .= static::extractArray($config[$i], $level + 1, $inList) . ',';
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
	 * @param mixed $value  Any value to transform
	 * @param int   $level  The current level of recursion (necessary for tab-indentation calculation)
	 * @param bool  $inList If true, the current array resides inside another list. Different rules may be applicable
	 *
	 * @return string
	 */
	protected static function transformConfigValue($value, int $level = 0, bool $inList = false): string
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
				return static::extractArray($value, ++$level, $inList);
			case "NULL":
				return "null";
			case "object":
				if (method_exists($value, '__toString')) {
					return sprintf('\'%s\'', $value);
				} elseif ($value instanceof \Serializable) {
					try {
						return $value->serialize();
					} catch (\Exception $e) {
						throw new \InvalidArgumentException(sprintf('Cannot serialize %s.', gettype($value)), $e);
					}
				} else {
					throw new \InvalidArgumentException(sprintf('%s is an object without stringify.', gettype($value)));
				}
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
