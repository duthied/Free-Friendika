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

/**
 * Return the default robots.txt
 */
class RobotsTxt extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$allDisallowed = [
			'/settings/',
			'/admin/',
			'/message/',
			'/search',
			'/help',
			'/proxy',
			'/photo',
			'/avatar',
		];

		header('Content-Type: text/plain');
		echo 'User-agent: *' . PHP_EOL;
		foreach ($allDisallowed as $disallowed) {
			echo 'Disallow: ' . $disallowed . PHP_EOL;
		}

		echo PHP_EOL;
		echo 'User-agent: ChatGPT-User' . PHP_EOL;
		echo 'Disallow: /' . PHP_EOL;

		echo PHP_EOL;
		echo 'User-agent: Google-Extended' . PHP_EOL;
		echo 'Disallow: /' . PHP_EOL;

		echo PHP_EOL;
		echo 'User-agent: GPTBot' . PHP_EOL;
		echo 'Disallow: /' . PHP_EOL;

		System::exit();
	}
}
