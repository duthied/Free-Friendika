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

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Model\Item;

/**
 * Posts items that where spooled because they couldn't be posted.
 */
class SpoolPost {
	public static function execute() {
		$path = get_spoolpath();

		if (($path != '') && is_writable($path)){
			if ($dh = opendir($path)) {
				while (($file = readdir($dh)) !== false) {

					// It is not named like a spool file, so we don't care.
					if (substr($file, 0, 5) != "item-") {
						continue;
					}

					$fullfile = $path."/".$file;

					// We don't care about directories either
					if (filetype($fullfile) != "file") {
						continue;
					}

					// We can't read or write the file? So we don't care about it.
					if (!is_writable($fullfile) || !is_readable($fullfile)) {
						continue;
					}

					$arr = json_decode(file_get_contents($fullfile), true);

					// If it isn't an array then it is no spool file
					if (!is_array($arr)) {
						continue;
					}

					// Skip if it doesn't seem to be an item array
					if (!isset($arr['uid']) && !isset($arr['uri']) && !isset($arr['network'])) {
						continue;
					}

					$result = Item::insert($arr);

					Logger::log("Spool file ".$file." stored: ".$result, Logger::DEBUG);
					unlink($fullfile);
				}
				closedir($dh);
			}
		}
	}
}
