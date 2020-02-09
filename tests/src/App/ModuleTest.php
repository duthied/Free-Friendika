<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

use Friendica\App;
use Friendica\Core\Config\IConfig;
use Friendica\Core\L10n;
use Friendica\LegacyModule;
use Friendica\Module\HTTPException\PageNotFound;
use Friendica\Module\WellKnown\HostMeta;
use Friendica\Test\DatabaseTest;

class ModuleTest extends DatabaseTest
{
	private function assertModule(array $assert, App\Module $module)
	{
		$this->assertEquals($assert['isBackend'], $module->isBackend());
		$this->assertEquals($assert['name'], $module->getName());
		$this->assertEquals($assert['class'], $module->getClassName());
	}

	/**
	 * Test the default module mode
	 */
	public function testDefault()
	{
		$module = new App\Module();

		$this->assertModule([
			'isBackend' => false,
			'name'      => App\Module::DEFAULT,
			'class'     => App\Module::DEFAULT_CLASS,
		], $module);
	}

	public function dataModuleName()
	{
		return [
			'default'                   => [
				'assert' => [
					'isBackend' => false,
					'name'      => 'network',
					'class'     => App\Module::DEFAULT_CLASS,
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
					'class'     => App\Module::DEFAULT_CLASS,
				],
				'args'   => new App\Arguments('with-strike.and-point/data/in',
					'with-strike.and-point/data/in',
					['with-strike.and-point', 'data', 'in'],
					3),
			],
			'withNothing'               => [
				'assert' => [
					'isBackend' => false,
					'name'      => App\Module::DEFAULT,
					'class'     => App\Module::DEFAULT_CLASS,
				],
				'args'   => new App\Arguments(),
			],
			'withIndex'                 => [
				'assert' => [
					'isBackend' => false,
					'name'      => App\Module::DEFAULT,
					'class'     => App\Module::DEFAULT_CLASS,
				],
				'args'   => new App\Arguments(),
			],
			'withBackendMod'    => [
				'assert' => [
					'isBackend' => true,
					'name'      => App\Module::BACKEND_MODULES[0],
					'class'     => App\Module::DEFAULT_CLASS,
				],
				'args'   => new App\Arguments(App\Module::BACKEND_MODULES[0] . '/data/in',
					App\Module::BACKEND_MODULES[0] . '/data/in',
					[App\Module::BACKEND_MODULES[0], 'data', 'in'],
					3),
			],
			'withFirefoxApp'            => [
				'assert' => [
					'isBackend' => false,
					'name'      => 'login',
					'class'     => App\Module::DEFAULT_CLASS,
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
		$module = (new App\Module())->determineModule($args);

		$this->assertModule($assert, $module);
	}

	public function dataModuleClass()
	{
		return [
			'default' => [
				'assert'  => App\Module::DEFAULT_CLASS,
				'name'    => App\Module::DEFAULT,
				'command' => App\Module::DEFAULT,
				'privAdd' => false,
			],
			'legacy'  => [
				'assert'  => LegacyModule::class,
				// API is one of the last modules to switch from legacy to new BaseModule
				// so this should be a stable test case until we completely switch ;-)
				'name'    => 'api',
				'command' => 'api/test/it',
				'privAdd' => false,
			],
			'new'     => [
				'assert'  => HostMeta::class,
				'not_required',
				'command' => '.well-known/host-meta',
				'privAdd' => false,
			],
			'404'     => [
				'assert'  => PageNotFound::class,
				'name'    => 'invalid',
				'command' => 'invalid',
				'privAdd' => false,
			]
		];
	}

	/**
	 * Test the determination of the module class
	 *
	 * @dataProvider dataModuleClass
	 */
	public function testModuleClass($assert, string $name, string $command, bool $privAdd)
	{
		$config = \Mockery::mock(IConfig::class);
		$config->shouldReceive('get')->with('config', 'private_addons', false)->andReturn($privAdd)->atMost()->once();

		$l10n = \Mockery::mock(L10n::class);
		$l10n->shouldReceive('t')->andReturnUsing(function ($args) { return $args; });

		$router = (new App\Router([], $l10n))->loadRoutes(include __DIR__ . '/../../../static/routes.config.php');

		$module = (new App\Module($name))->determineClass(new App\Arguments('', $command), $router, $config);

		$this->assertEquals($assert, $module->getClassName());
	}

	/**
	 * Test that modules are immutable
	 */
	public function testImmutable()
	{
		$module = new App\Module();

		$moduleNew = $module->determineModule(new App\Arguments());

		$this->assertNotSame($moduleNew, $module);
	}
}
