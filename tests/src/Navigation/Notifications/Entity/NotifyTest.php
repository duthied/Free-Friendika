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

namespace Friendica\Test\src\Navigation\Notifications\Entity;

use Friendica\Navigation\Notifications\Entity\Notify;
use Friendica\Test\FixtureTest;

class NotifyTest extends FixtureTest
{
	public function dataFormatNotify(): array
	{
		return [
			'xss-notify' => [
				'name'      => 'Whiskers',
				'message'   => '{0} commented in the thread "If my username causes a pop up in a piece of software, that softwar…" from <script>alert("Tek");</script>',
				'assertion' => '<span class="contactname">Whiskers</span> commented in the thread &quot;If my username causes a pop up in a piece of software, that softwar…&quot; from &lt;script&gt;alert(&quot;Tek&quot;);&lt;/script&gt;',
			],
		];
	}

	/**
	 * @dataProvider dataFormatNotify
	 */
	public function testFormatNotify(string $name, string $message, string $assertion)
	{
		self::assertEquals($assertion, Notify::formatMessage($name, $message));
	}
}
