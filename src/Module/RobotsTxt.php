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

namespace Friendica\Module;

use Friendica\BaseModule;

/**
 * Return the default robots.txt
 */
class RobotsTxt extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$allDisalloweds = [
			'/settings/',
			'/admin/',
			'/message/',
			'/search',
			'/help',
			'/proxy',
		];

		header('Content-Type: text/plain');
		echo 'User-agent: *' . PHP_EOL;
		foreach ($allDisalloweds as $disallowed) {
			echo 'Disallow: ' . $disallowed . PHP_EOL;
		}
		exit();
	}
}
