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

namespace Friendica\Test\src\Core\Hooks\Util;

use Friendica\Core\Addon\Capability\ICanLoadAddons;
use Friendica\Core\Hooks\Capability\ICanRegisterStrategies;
use Friendica\Core\Hooks\Exceptions\HookConfigException;
use Friendica\Core\Hooks\Util\StrategiesFileManager;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\VFSTrait;
use org\bovigo\vfs\vfsStream;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class StrategiesFileManagerTest extends MockedTest
{
	use VFSTrait;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpVfsDir();
	}

	public function dataHooks(): array
	{
		return [
			'normal' => [
				'content' => <<<EOF
<?php

return [
		\Psr\Log\LoggerInterface::class => [
			\Psr\Log\NullLogger::class => [''],
		],
];
EOF,
				'addonsArray'      => [],
				'assertStrategies' => [
					[LoggerInterface::class, NullLogger::class, ''],
				],
			],
			'normalWithString' => [
				'content' => <<<EOF
<?php

return [
		\Psr\Log\LoggerInterface::class => [
			\Psr\Log\NullLogger::class => '',
		],
];
EOF,
				'addonsArray'      => [],
				'assertStrategies' => [
					[LoggerInterface::class, NullLogger::class, ''],
				],
			],
			'withAddons' => [
				'content' => <<<EOF
<?php

return [
		\Psr\Log\LoggerInterface::class => [
			\Psr\Log\NullLogger::class => [''],
		],
];
EOF,
				'addonsArray' => [
					\Psr\Log\LoggerInterface::class => [
						\Psr\Log\NullLogger::class => ['null'],
					],
				],
				'assertStrategies' => [
					[LoggerInterface::class, NullLogger::class, ''],
					[LoggerInterface::class, NullLogger::class, 'null'],
				],
			],
			'withAddonsWithString' => [
				'content' => <<<EOF
<?php

return [
		\Psr\Log\LoggerInterface::class => [
			\Psr\Log\NullLogger::class => [''],
		],
];
EOF,
				'addonsArray' => [
					\Psr\Log\LoggerInterface::class => [
						\Psr\Log\NullLogger::class => 'null',
					],
				],
				'assertStrategies' => [
					[LoggerInterface::class, NullLogger::class, ''],
					[LoggerInterface::class, NullLogger::class, 'null'],
				],
			],
			// This should work because unique name convention is part of the instance manager logic, not of the file-infrastructure layer
			'withAddonsDoubleNamed' => [
				'content' => <<<EOF
<?php

return [
		\Psr\Log\LoggerInterface::class => [
			\Psr\Log\NullLogger::class => [''],
		],
];
EOF,
				'addonsArray' => [
					\Psr\Log\LoggerInterface::class => [
						\Psr\Log\NullLogger::class => [''],
					],
				],
				'assertStrategies' => [
					[LoggerInterface::class, NullLogger::class, ''],
					[LoggerInterface::class, NullLogger::class, ''],
				],
			],
		];
	}

	/**
	 * @dataProvider dataHooks
	 */
	public function testSetupHooks(string $content, array $addonsArray, array $assertStrategies)
	{
		vfsStream::newFile(StrategiesFileManager::STATIC_DIR . '/' . StrategiesFileManager::CONFIG_NAME . '.config.php')
			->withContent($content)
			->at($this->root);

		$addonLoader = \Mockery::mock(ICanLoadAddons::class);
		$addonLoader->shouldReceive('getActiveAddonConfig')->andReturn($addonsArray)->once();

		$hookFileManager = new StrategiesFileManager($this->root->url(), $addonLoader);

		$instanceManager = \Mockery::mock(ICanRegisterStrategies::class);
		foreach ($assertStrategies as $assertStrategy) {
			$instanceManager->shouldReceive('registerStrategy')->withArgs($assertStrategy)->once();
		}

		$hookFileManager->loadConfig();
		$hookFileManager->setupStrategies($instanceManager);

		self::expectNotToPerformAssertions();
	}

	/**
	 * Test the exception in case the strategies.config.php file is missing
	 */
	public function testMissingStrategiesFile()
	{
		$addonLoader     = \Mockery::mock(ICanLoadAddons::class);
		$instanceManager = \Mockery::mock(ICanRegisterStrategies::class);
		$hookFileManager = new StrategiesFileManager($this->root->url(), $addonLoader);

		self::expectException(HookConfigException::class);
		self::expectExceptionMessage(sprintf('config file %s does not exist.',
				$this->root->url() . '/' . StrategiesFileManager::STATIC_DIR . '/' . StrategiesFileManager::CONFIG_NAME . '.config.php'));

		$hookFileManager->loadConfig();
	}

	/**
	 * Test the exception in case the strategies.config.php file is wrong
	 */
	public function testWrongStrategiesFile()
	{
		$addonLoader     = \Mockery::mock(ICanLoadAddons::class);
		$instanceManager = \Mockery::mock(ICanRegisterStrategies::class);
		$hookFileManager = new StrategiesFileManager($this->root->url(), $addonLoader);

		vfsStream::newFile(StrategiesFileManager::STATIC_DIR . '/' . StrategiesFileManager::CONFIG_NAME . '.config.php')
				 ->withContent("<?php return 'WRONG_CONTENT';")
				 ->at($this->root);

		self::expectException(HookConfigException::class);
		self::expectExceptionMessage(sprintf('Error loading config file %s.',
			$this->root->url() . '/' . StrategiesFileManager::STATIC_DIR . '/' . StrategiesFileManager::CONFIG_NAME . '.config.php'));

		$hookFileManager->loadConfig();
	}
}
