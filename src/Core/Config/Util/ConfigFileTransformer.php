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
	public static function encode(array $data): string
	{
		$dataString = '<?php' . PHP_EOL . PHP_EOL;
		$dataString .= 'return [' . PHP_EOL;

		$categories = array_keys($data);

		foreach ($categories as $category) {
			$dataString .= "\t'$category' => [" . PHP_EOL;

			if (is_array($data[$category])) {
				$keys = array_keys($data[$category]);

				foreach ($keys as $key) {
					$dataString .= static::mapConfigValue($key, $data[$category][$key]);
				}
			}
			$dataString .= "\t]," . PHP_EOL;
		}

		$dataString .= "];" . PHP_EOL;

		return $dataString;
	}

	protected static function extractArray(array $config, int $level = 0): string
	{
		$string = '';

		foreach ($config as $configKey => $configValue) {
			$string .= static::mapConfigValue($configKey, $configValue, $level);
		}

		return $string;
	}

	protected static function mapConfigValue(string $key, $value, $level = 0): string
	{
		$string = str_repeat("\t", $level + 2) . "'$key' => ";

		if (is_array($value)) {
			$string .= "[" . PHP_EOL;
			$string .= static::extractArray($value, ++$level);
			$string .= str_repeat("\t", $level + 1) . '],';
		} elseif (is_bool($value)) {
			$string .= ($value ? 'true' : 'false') . ",";
		} elseif (is_numeric($value)) {
			$string .= $value . ",";
		} else {
			$string .= sprintf('\'%s\',', $value);
		}

		$string .= PHP_EOL;

		return $string;
	}
}
