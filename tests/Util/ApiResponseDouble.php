<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Test\Util;

use Friendica\Module\Api\ApiResponse;

class ApiResponseDouble extends ApiResponse
{
	/**
	 * The header list
	 *
	 * @var string[][]
	 */
	protected static $header = [];

	/**
	 * The printed output
	 *
	 * @var string
	 */
	protected static $output = '';

	/**
	 * @return string[]
	 */
	public static function getHeader(): array
	{
		return static::$header;
	}

	/**
	 * @return string
	 */
	public static function getOutput(): string
	{
		return static::$output;
	}

	public static function reset()
	{
		self::$output = '';
		self::$header = [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function setHeader(?string $header = null, ?string $key = null): void
	{
		if (!isset($header) && !empty($key)) {
			unset(static::$header[$key]);
		}

		if (isset($header)) {
			if (empty($key)) {
				static::$header[] = $header;
			} else {
				static::$header[$key] = $header;
			}
		}
	}

	protected function printOutput(string $output)
	{
		static::$output .= $output;
	}
}
