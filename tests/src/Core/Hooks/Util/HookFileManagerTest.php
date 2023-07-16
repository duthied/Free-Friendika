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

use Friendica\Core\Addon\Capabilities\ICanLoadAddons;
use Friendica\Core\Hooks\Capabilities\ICanRegisterInstances;
use Friendica\Core\Hooks\Exceptions\HookConfigException;
use Friendica\Core\Hooks\Util\HookFileManager;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\VFSTrait;
use org\bovigo\vfs\vfsStream;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class HookFileManagerTest extends MockedTest
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
	\Friendica\Core\Hooks\Capabilities\BehavioralHookType::STRATEGY => [
		\Psr\Log\LoggerInterface::class => [
			\Psr\Log\NullLogger::class => [''],
		],
	],
	\Friendica\Core\Hooks\Capabilities\BehavioralHookType::DECORATOR => [
		\Psr\Log\LoggerInterface::class => [
			\Psr\Log\NullLogger::class,
		],
	],
];
EOF,
				'addonsArray' => [],
				'assertStrategies' => [
					[LoggerInterface::class, NullLogger::class, ''],
				],
				'assertDecorators' => [
					[LoggerInterface::class, NullLogger::class],
				],
			],
			'normalWithString' => [
				'content' => <<<EOF
<?php

return [
	\Friendica\Core\Hooks\Capabilities\BehavioralHookType::STRATEGY => [
		\Psr\Log\LoggerInterface::class => [
			\Psr\Log\NullLogger::class => '',
		],
	],
	\Friendica\Core\Hooks\Capabilities\BehavioralHookType::DECORATOR => [
		\Psr\Log\LoggerInterface::class => \Psr\Log\NullLogger::class,
	],
];
EOF,
				'addonsArray' => [],
				'assertStrategies' => [
					[LoggerInterface::class, NullLogger::class, ''],
				],
				'assertDecorators' => [
					[LoggerInterface::class, NullLogger::class],
				],
			],
			'withAddons' => [
				'content' => <<<EOF
<?php

return [
	\Friendica\Core\Hooks\Capabilities\BehavioralHookType::STRATEGY => [
		\Psr\Log\LoggerInterface::class => [
			\Psr\Log\NullLogger::class => [''],
		],
	],
];
EOF,
				'addonsArray' => [
					\Friendica\Core\Hooks\Capabilities\BehavioralHookType::STRATEGY => [
						\Psr\Log\LoggerInterface::class => [
							\Psr\Log\NullLogger::class => ['null'],
						],
					],
				],
				'assertStrategies' => [
					[LoggerInterface::class, NullLogger::class, ''],
					[LoggerInterface::class, NullLogger::class, 'null'],
				],
				'assertDecorators' => [],
			],
			'withAddonsWithString' => [
				'content' => <<<EOF
<?php

return [
	\Friendica\Core\Hooks\Capabilities\BehavioralHookType::STRATEGY => [
		\Psr\Log\LoggerInterface::class => [
			\Psr\Log\NullLogger::class => [''],
		],
	],
];
EOF,
				'addonsArray' => [
					\Friendica\Core\Hooks\Capabilities\BehavioralHookType::STRATEGY => [
						\Psr\Log\LoggerInterface::class => [
							\Psr\Log\NullLogger::class => 'null',
						],
					],
				],
				'assertStrategies' => [
					[LoggerInterface::class, NullLogger::class, ''],
					[LoggerInterface::class, NullLogger::class, 'null'],
				],
				'assertDecorators' => [],
			],
			// This should work because unique name convention is part of the instance manager logic, not of the file-infrastructure layer
			'withAddonsDoubleNamed' => [
				'content' => <<<EOF
<?php

return [
	\Friendica\Core\Hooks\Capabilities\BehavioralHookType::STRATEGY => [
		\Psr\Log\LoggerInterface::class => [
			\Psr\Log\NullLogger::class => [''],
		],
	],
];
EOF,
				'addonsArray' => [
					\Friendica\Core\Hooks\Capabilities\BehavioralHookType::STRATEGY => [
						\Psr\Log\LoggerInterface::class => [
							\Psr\Log\NullLogger::class => [''],
						],
					],
				],
				'assertStrategies' => [
					[LoggerInterface::class, NullLogger::class, ''],
					[LoggerInterface::class, NullLogger::class, ''],
				],
				'assertDecorators' => [],
			],
			'withWrongContentButAddons' => [
				'content' => <<<EOF
<?php

return [
	'REALLY_WRONG' => [
		\Psr\Log\LoggerInterface::class => [
			\Psr\Log\NullLogger::class => [''],
		],
	],
];
EOF,
				'addonsArray' => [
					\Friendica\Core\Hooks\Capabilities\BehavioralHookType::STRATEGY => [
						\Psr\Log\LoggerInterface::class => [
							\Psr\Log\NullLogger::class => [''],
						],
					],
				],
				'assertStrategies' => [
					[LoggerInterface::class, NullLogger::class, ''],
				],
				'assertDecorators' => [],
			],
		];
	}

	/**
	 * @dataProvider dataHooks
	 */
	public function testSetupHooks(string $content, array $addonsArray, array $assertStrategies, array $assertDecorators)
	{
		vfsStream::newFile('static/hooks.config.php')
			->withContent($content)
			->at($this->root);

		$addonLoader = \Mockery::mock(ICanLoadAddons::class);
		$addonLoader->shouldReceive('getActiveAddonConfig')->andReturn($addonsArray)->once();

		$hookFileManager = new HookFileManager($this->root->url(), $addonLoader);

		$instanceManager = \Mockery::mock(ICanRegisterInstances::class);
		foreach ($assertStrategies as $assertStrategy) {
			$instanceManager->shouldReceive('registerStrategy')->withArgs($assertStrategy)->once();
		}

		foreach ($assertDecorators as $assertDecorator) {
			$instanceManager->shouldReceive('registerDecorator')->withArgs($assertDecorator)->once();
		}

		$hookFileManager->setupHooks($instanceManager);

		self::expectNotToPerformAssertions();
	}

	/**
	 * Test the exception in case the hooks.config.php file is missing
	 */
	public function testMissingHooksFile()
	{
		$addonLoader     = \Mockery::mock(ICanLoadAddons::class);
		$instanceManager = \Mockery::mock(ICanRegisterInstances::class);
		$hookFileManager = new HookFileManager($this->root->url(), $addonLoader);

		self::expectException(HookConfigException::class);
		self::expectExceptionMessage(sprintf('config file %s does not exist.',
				$this->root->url() . '/' . HookFileManager::STATIC_DIR . '/' . HookFileManager::CONFIG_NAME . '.config.php'));

		$hookFileManager->setupHooks($instanceManager);
	}

	/**
	 * Test the exception in case the hooks.config.php file is wrong
	 */
	public function testWrongHooksFile()
	{
		$addonLoader     = \Mockery::mock(ICanLoadAddons::class);
		$instanceManager = \Mockery::mock(ICanRegisterInstances::class);
		$hookFileManager = new HookFileManager($this->root->url(), $addonLoader);

		vfsStream::newFile('static/hooks.config.php')
				 ->withContent("<php return 'WRONG_CONTENT';")
				 ->at($this->root);

		self::expectException(HookConfigException::class);
		self::expectExceptionMessage(sprintf('Error loading config file %s.',
			$this->root->url() . '/' . HookFileManager::STATIC_DIR . '/' . HookFileManager::CONFIG_NAME . '.config.php'));

		$hookFileManager->setupHooks($instanceManager);
	}
}
