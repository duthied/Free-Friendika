<?php

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
	\Friendica\Core\Hooks\Capabilities\HookType::STRATEGY => [
		\Psr\Log\LoggerInterface::class => [
			\Psr\Log\NullLogger::class => [''],
		],
	],
	\Friendica\Core\Hooks\Capabilities\HookType::DECORATOR => [
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
	\Friendica\Core\Hooks\Capabilities\HookType::STRATEGY => [
		\Psr\Log\LoggerInterface::class => [
			\Psr\Log\NullLogger::class => '',
		],
	],
	\Friendica\Core\Hooks\Capabilities\HookType::DECORATOR => [
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
	\Friendica\Core\Hooks\Capabilities\HookType::STRATEGY => [
		\Psr\Log\LoggerInterface::class => [
			\Psr\Log\NullLogger::class => [''],
		],
	],
];
EOF,
				'addonsArray' => [
					\Friendica\Core\Hooks\Capabilities\HookType::STRATEGY => [
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
	\Friendica\Core\Hooks\Capabilities\HookType::STRATEGY => [
		\Psr\Log\LoggerInterface::class => [
			\Psr\Log\NullLogger::class => [''],
		],
	],
];
EOF,
				'addonsArray' => [
					\Friendica\Core\Hooks\Capabilities\HookType::STRATEGY => [
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
	\Friendica\Core\Hooks\Capabilities\HookType::STRATEGY => [
		\Psr\Log\LoggerInterface::class => [
			\Psr\Log\NullLogger::class => [''],
		],
	],
];
EOF,
				'addonsArray' => [
					\Friendica\Core\Hooks\Capabilities\HookType::STRATEGY => [
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
					\Friendica\Core\Hooks\Capabilities\HookType::STRATEGY => [
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
