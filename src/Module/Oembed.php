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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content;
use Friendica\DI;
use Friendica\Util\Strings;

/**
 * Oembed module
 *
 * Displays stored embed content based on a base64 hash of a remote URL
 *
 * Example: /oembed/aHR0cHM6Ly9...
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class Oembed extends BaseModule
{
	public static function content(array $parameters = [])
	{
		$a = DI::app();

		// Unused form: /oembed/b2h?url=...
		if ($a->argv[1] == 'b2h') {
			$url = ["", trim(hex2bin($_REQUEST['url']))];
			echo Content\OEmbed::replaceCallback($url);
			exit();
		}

		// Unused form: /oembed/h2b?text=...
		if ($a->argv[1] == 'h2b') {
			$text = trim(hex2bin($_REQUEST['text']));
			echo Content\OEmbed::HTML2BBCode($text);
			exit();
		}

		// @TODO: Replace with parameter from router
		if ($a->argc == 2) {
			echo '<html><body>';
			$url = Strings::base64UrlDecode($a->argv[1]);
			$j = Content\OEmbed::fetchURL($url);

			// workaround for media.ccc.de (and any other endpoint that return size 0)
			if (substr($j->html, 0, 7) == "<iframe" && strstr($j->html, 'width="0"')) {
				$j->html = '<style>html,body{margin:0;padding:0;} iframe{width:100%;height:100%;}</style>' . $j->html;
				$j->html = str_replace('width="0"', '', $j->html);
				$j->html = str_replace('height="0"', '', $j->html);
			}
			echo $j->html;
			echo '</body></html>';
		}
		exit();
	}
}
