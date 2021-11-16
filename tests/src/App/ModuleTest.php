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

namespace Friendica\Test\src\App;

use Dice\Dice;
use Friendica\App;
use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Lock\Capability\ICanLock;
use Friendica\LegacyModule;
use Friendica\Module\HTTPException\PageNotFound;
use Friendica\Module\WellKnown\HostMeta;
use Friendica\Test\DatabaseTest;
use Mockery;

class ModuleTest extends DatabaseTest
{
	private function assertModule(array $assert, App\ModuleController $module)
	{
		self::assertEquals($assert['isBackend'], $module->isBackend());
		self::assertEquals($assert['name'], $module->getName());
		self::assertEquals($assert['class'], $module->getModule());
	}

	/**
	 * Test the default module mode
	 */
	public function testDefault()
	{
		$module = new App\ModuleController();

		$defaultClass = App\ModuleController::DEFAULT_CLASS;

		self::assertModule([
			'isBackend' => false,
			'name'      => App\ModuleController::DEFAULT,
			'class'     => new $defaultClass(),
		], $module);
	}

	public function dataModuleName()
	{
		$defaultClass = App\ModuleController::DEFAULT_CLASS;

		return [
			'default'                   => [
				'assert' => [
					'isBackend' => false,
					'name'      => 'network',
					'class'     => new $defaultClass(),
				],
				'args'   => new App\Arguments('network/data/in',
					'network/data/in',
					['network', 'data', 'in'],
					3),
			],
			'withStrikeAndPoint'        => [
				'assert' => [
					'isBackend' => false,
					'name'      => 'with_strike_and_point',
					'class'     => new $defaultClass(),
				],
				'args'   => new App\Arguments('with-strike.and-point/data/in',
					'with-strike.and-point/data/in',
					['with-strike.and-point', 'data', 'in'],
					3),
			],
			'withNothing'               => [
				'assert' => [
					'isBackend' => false,
					'name'      => App\ModuleController::DEFAULT,
					'class'     => new $defaultClass(),
				],
				'args'   => new App\Arguments(),
			],
			'withIndex'                 => [
				'assert' => [
					'isBackend' => false,
					'name'      => App\ModuleController::DEFAULT,
					'class'     => new $defaultClass(),
				],
				'args'   => new App\Arguments(),
			],
			'withBackendMod'    => [
				'assert' => [
					'isBackend' => true,
					'name'      => App\ModuleController::BACKEND_MODULES[0],
					'class'     => new $defaultClass(),
				],
				'args'   => new App\Arguments(App\ModuleController::BACKEND_MODULES[0] . '/data/in',
					App\ModuleController::BACKEND_MODULES[0] . '/data/in',
					[App\ModuleController::BACKEND_MODULES[0], 'data', 'in'],
					3),
			],
			'withFirefoxApp'            => [
				'assert' => [
					'isBackend' => false,
					'name'      => 'login',
					'class'     => new $defaultClass(),
				],
				'args'   => new App\Arguments('users/sign_in',
					'users/sign_in',
					['users', 'sign_in'],
					3),
			],
		];
	}

	/**
	 * Test the module name and backend determination
	 *
	 * @dataProvider dataModuleName
	 */
	public function testModuleName(array $assert, App\Arguments $args)
	{
		$module = (new App\ModuleController())->determineName($args);

		self::assertModule($assert, $module);
	}

	public function dataModuleClass()
	{
		return [
			'default' => [
				'assert'  => App\ModuleController::DEFAULT_CLASS,
				'name'    => App\ModuleController::DEFAULT,
				'command' => App\ModuleController::DEFAULT,
				'privAdd' => false,
				'args'    => [],
			],
			'legacy'  => [
				'assert'  => LegacyModule::class,
				'name'    => 'display',
				'command' => 'display/test/it',
				'privAdd' => false,
				'args'    => [__DIR__ . '/../../datasets/legacy/legacy.php'],
			],
			'new'     => [
				'assert'  => HostMeta::class,
				'not_required',
				'command' => '.well-known/host-meta',
				'privAdd' => false,
				'args'    => [],
			],
			'404'     => [
				'assert'  => PageNotFound::class,
				'name'    => 'invalid',
				'command' => 'invalid',
				'privAdd' => false,
				'args'    => [],
			]
		];
	}

	/**
	 * Test the determination of the module class
	 *
	 * @dataProvider dataModuleClass
	 */
	public function testModuleClass($assert, string $name, string $command, bool $privAdd, array $args)
	{
		$config = Mockery::mock(IManageConfigValues::class);
		$config->shouldReceive('get')->with('config', 'private_addons', false)->andReturn($privAdd)->atMost()->once();

		$l10n = Mockery::mock(L10n::class);
		$l10n->shouldReceive('t')->andReturnUsing(function ($args) { return $args; });

		$cache = Mockery::mock(ICanCache::class);
		$cache->shouldReceive('get')->with('routerDispatchData')->andReturn('')->atMost()->once();
		$cache->shouldReceive('get')->with('lastRoutesFileModifiedTime')->andReturn('')->atMost()->once();
		$cache->shouldReceive('set')->withAnyArgs()->andReturn(false)->atMost()->twice();

		$lock = Mockery::mock(ICanLock::class);
		$lock->shouldReceive('acquire')->andReturn(true);
		$lock->shouldReceive('isLocked')->andReturn(false);

		$router = (new App\Router([], __DIR__ . '/../../../static/routes.config.php', $l10n, $cache, $lock));

		$dice = Mockery::mock(Dice::class);

		$dice->shouldReceive('create')->andReturn(new $assert(...$args));

		$module = (new App\ModuleController($name))->determineClass(new App\Arguments('', $command), $router, $config, $dice);

		self::assertEquals($assert, $module->getModule()->getClassName());
	}

	/**
	 * Test that modules are immutable
	 */
	public function testImmutable()
	{
		$module = new App\ModuleController();

		$moduleNew = $module->determineName(new App\Arguments());

		self::assertNotSame($moduleNew, $module);
	}
}
