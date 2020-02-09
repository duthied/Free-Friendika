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
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Util\Strings;

/**
 * Hashtag module.
 */
class Hashtag extends BaseModule
{

	public static function content(array $parameters = [])
	{
		$result = [];

		$t = Strings::escapeHtml($_REQUEST['t']);
		if (empty($t)) {
			System::jsonExit($result);
		}

		$taglist = DBA::p("SELECT DISTINCT(`term`) FROM `term` WHERE `term` LIKE ? AND `type` = ? ORDER BY `term`",
			$t . '%',
			intval(TERM_HASHTAG)
		);
		while ($tag = DBA::fetch($taglist)) {
			$result[] = ['text' => $tag['term']];
		}
		DBA::close($taglist);

		System::jsonExit($result);
	}
}
