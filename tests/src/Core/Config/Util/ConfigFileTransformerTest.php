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
			'friendica.local' => [
				'configFile' => (dirname(__DIR__, 4) . '/datasets/config/transformer/C.node.config.php'),
			],
			'friendica.local.2' => [
				'configFile' => (dirname(__DIR__, 4) . '/datasets/config/transformer/D.node.config.php'),
			],
			'object_invalid' => [
				'configFile' => (dirname(__DIR__, 4) . '/datasets/config/transformer/object.node.config.php'),
				'assertException' => true,
			],
			'ressource_invalid' => [
				'configFile' => (dirname(__DIR__, 4) . '/datasets/config/transformer/ressource.node.config.php'),
				'assertException' => true,
			],
			'test_types' => [
				'configFile' => (dirname(__DIR__, 4) . '/datasets/config/transformer/types.node.config.php'),
			],
			'small_types' => [
				'configFile' => (dirname(__DIR__, 4) . '/datasets/config/transformer/small_types.node.config.php'),
			]
		];
	}

	/**
	 * Tests if the given config will be decoded into an array and encoded into the same string again
	 *
	 * @dataProvider dataTests
	 */
	public function testConfigFile(string $configFile, bool $assertException = false)
	{
		$dataArray = include $configFile;

		if ($assertException) {
			self::expectException(\InvalidArgumentException::class);
		}

		$newConfig = ConfigFileTransformer::encode($dataArray);

		self::assertEquals(file_get_contents($configFile), $newConfig);
	}
}
