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

namespace Friendica\Content\Text;

class Plaintext
{
	/**
	 * Shortens message
	 *
	 * @param  string $msg
	 * @param  int    $limit
	 * @return string
	 *
	 * @todo For Twitter URLs aren't shortened, but they have to be calculated as if.
	 */
	public static function shorten($msg, $limit)
	{
		$lines = explode("\n", $msg);
		$msg = "";
		$recycle = html_entity_decode("&#x2672; ", ENT_QUOTES, 'UTF-8');
		$ellipsis = html_entity_decode("&#x2026;", ENT_QUOTES, 'UTF-8');
		foreach ($lines as $row => $line) {
			if (iconv_strlen(trim($msg . "\n" . $line), "UTF-8") <= $limit) {
				$msg = trim($msg . "\n" . $line);
			} elseif (($msg == "") || (($row == 1) && (substr($msg, 0, 4) == $recycle))) {
				// Is the new message empty by now or is it a reshared message?
				$msg = iconv_substr(iconv_substr(trim($msg . "\n" . $line), 0, $limit, "UTF-8"), 0, -3, "UTF-8") . $ellipsis;
			} else {
				break;
			}
		}

		return $msg;
	}

	/**
	 * Returns the character positions of the provided boundaries, optionally skipping a number of first occurrences
	 *
	 * @param string $text        Text to search
	 * @param string $open        Left boundary
	 * @param string $close       Right boundary
	 * @param int    $occurrences Number of first occurrences to skip
	 * @return boolean|array
	 */
	public static function getBoundariesPosition($text, $open, $close, $occurrences = 0)
	{
		if ($occurrences < 0) {
			$occurrences = 0;
		}

		$start_pos = -1;
		for ($i = 0; $i <= $occurrences; $i++) {
			if ($start_pos !== false) {
				$start_pos = strpos($text, $open, $start_pos + 1);
			}
		}

		if ($start_pos === false) {
			return false;
		}

		$end_pos = strpos($text, $close, $start_pos);

		if ($end_pos === false) {
			return false;
		}

		$res = ['start' => $start_pos, 'end' => $end_pos];

		return $res;
	}
}
