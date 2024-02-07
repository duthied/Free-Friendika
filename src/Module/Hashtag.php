<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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
	protected function rawContent(array $request = [])
	{
		$result = [];

		if (empty($request['t'])) {
			$this->jsonExit($result);
		}

		$taglist = DBA::select(
			'tag',
			['name'],
			["`name` LIKE ?", Strings::escapeHtml($request['t']) . "%"],
			['order' => ['name'], 'limit' => 100]
		);
		while ($tag = DBA::fetch($taglist)) {
			$result[] = ['text' => $tag['name']];
		}
		DBA::close($taglist);

		$this->jsonExit($result);
	}
}
