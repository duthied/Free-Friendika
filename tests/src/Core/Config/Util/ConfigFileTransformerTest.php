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

namespace Friendica\Test\src\Core\Config\Util;

use Friendica\Core\Config\Util\ConfigFileTransformer;
use Friendica\Test\MockedTest;

class ConfigFileTransformerTest extends MockedTest
{
	public function dataTests()
	{
		return [
			'default' => [
				'configFile' => (dirname(__DIR__, 4) . '/datasets/config/A.node.config.php'),
			],
			'extended' => [
				'configFile' => (dirname(__DIR__, 4) . '/datasets/config/B.node.config.php'),
			],
		];
	}

	/**
	 * Tests if the given config will be decoded into an array and encoded into the same string again
	 *
	 * @dataProvider dataTests
	 */
	public function testConfigFile(string $configFile)
	{
		$dataArray = include $configFile;

		$newConfig = ConfigFileTransformer::encode($dataArray);

		self::assertEquals(file_get_contents($configFile), $newConfig);
	}
}
