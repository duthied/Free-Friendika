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

use Friendica\App\Router;
use Friendica\Core\L10n;
use Friendica\Module;
use Friendica\Network\HTTPException\MethodNotAllowedException;
use Friendica\Network\HTTPException\NotFoundException;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
	/** @var L10n|MockInterface */
	private $l10n;

	protected function setUp()
	{
		parent::setUp();

		$this->l10n = \Mockery::mock(L10n::class);
		$this->l10n->shouldReceive('t')->andReturnUsing(function ($args) { return $args; });
	}

	public function testGetModuleClass()
	{
		$router = new Router(['REQUEST_METHOD' => Router::GET], $this->l10n);

		$routeCollector = $router->getRouteCollector();
		$routeCollector->addRoute([Router::GET], '/', 'IndexModuleClassName');
		$routeCollector->addRoute([Router::GET], '/test', 'TestModuleClassName');
		$routeCollector->addRoute([Router::GET, Router::POST], '/testgetpost', 'TestGetPostModuleClassName');
		$routeCollector->addRoute([Router::GET], '/test/sub', 'TestSubModuleClassName');
		$routeCollector->addRoute([Router::GET], '/optional[/option]', 'OptionalModuleClassName');
		$routeCollector->addRoute([Router::GET], '/variable/{var}', 'VariableModuleClassName');
		$routeCollector->addRoute([Router::GET], '/optionalvariable[/{option}]', 'OptionalVariableModuleClassName');

		$this->assertEquals('IndexModuleClassName', $router->getModuleClass('/'));
		$this->assertEquals('TestModuleClassName', $router->getModuleClass('/test'));
		$this->assertEquals('TestGetPostModuleClassName', $router->getModuleClass('/testgetpost'));
		$this->assertEquals('TestSubModuleClassName', $router->getModuleClass('/test/sub'));
		$this->assertEquals('OptionalModuleClassName', $router->getModuleClass('/optional'));
		$this->assertEquals('OptionalModuleClassName', $router->getModuleClass('/optional/option'));
		$this->assertEquals('VariableModuleClassName', $router->getModuleClass('/variable/123abc'));
		$this->assertEquals('OptionalVariableModuleClassName', $router->getModuleClass('/optionalvariable'));
		$this->assertEquals('OptionalVariableModuleClassName', $router->getModuleClass('/optionalvariable/123abc'));
	}

	public function testPostModuleClass()
	{
		$router = new Router(['REQUEST_METHOD' => Router::POST], $this->l10n);

		$routeCollector = $router->getRouteCollector();
		$routeCollector->addRoute([Router::POST], '/', 'IndexModuleClassName');
		$routeCollector->addRoute([Router::POST], '/test', 'TestModuleClassName');
		$routeCollector->addRoute([Router::GET, Router::POST], '/testgetpost', 'TestGetPostModuleClassName');
		$routeCollector->addRoute([Router::POST], '/test/sub', 'TestSubModuleClassName');
		$routeCollector->addRoute([Router::POST], '/optional[/option]', 'OptionalModuleClassName');
		$routeCollector->addRoute([Router::POST], '/variable/{var}', 'VariableModuleClassName');
		$routeCollector->addRoute([Router::POST], '/optionalvariable[/{option}]', 'OptionalVariableModuleClassName');

		$this->assertEquals('IndexModuleClassName', $router->getModuleClass('/'));
		$this->assertEquals('TestModuleClassName', $router->getModuleClass('/test'));
		$this->assertEquals('TestGetPostModuleClassName', $router->getModuleClass('/testgetpost'));
		$this->assertEquals('TestSubModuleClassName', $router->getModuleClass('/test/sub'));
		$this->assertEquals('OptionalModuleClassName', $router->getModuleClass('/optional'));
		$this->assertEquals('OptionalModuleClassName', $router->getModuleClass('/optional/option'));
		$this->assertEquals('VariableModuleClassName', $router->getModuleClass('/variable/123abc'));
		$this->assertEquals('OptionalVariableModuleClassName', $router->getModuleClass('/optionalvariable'));
		$this->assertEquals('OptionalVariableModuleClassName', $router->getModuleClass('/optionalvariable/123abc'));
	}

	public function testGetModuleClassNotFound()
	{
		$this->expectException(NotFoundException::class);

		$router = new Router(['REQUEST_METHOD' => Router::GET], $this->l10n);

		$router->getModuleClass('/unsupported');
	}

	public function testGetModuleClassNotFoundTypo()
	{
		$this->expectException(NotFoundException::class);

		$router = new Router(['REQUEST_METHOD' => Router::GET], $this->l10n);

		$routeCollector = $router->getRouteCollector();
		$routeCollector->addRoute([Router::GET], '/test', 'TestModuleClassName');

		$router->getModuleClass('/tes');
	}

	public function testGetModuleClassNotFoundOptional()
	{
		$this->expectException(NotFoundException::class);

		$router = new Router(['REQUEST_METHOD' => Router::GET], $this->l10n);

		$routeCollector = $router->getRouteCollector();
		$routeCollector->addRoute([Router::GET], '/optional[/option]', 'OptionalModuleClassName');

		$router->getModuleClass('/optional/opt');
	}

	public function testGetModuleClassNotFoundVariable()
	{
		$this->expectException(NotFoundException::class);

		$router = new Router(['REQUEST_METHOD' => Router::GET], $this->l10n);

		$routeCollector = $router->getRouteCollector();
		$routeCollector->addRoute([Router::GET], '/variable/{var}', 'VariableModuleClassName');

		$router->getModuleClass('/variable');
	}

	public function testGetModuleClassMethodNotAllowed()
	{
		$this->expectException(MethodNotAllowedException::class);

		$router = new Router(['REQUEST_METHOD' => Router::POST], $this->l10n);

		$routeCollector = $router->getRouteCollector();
		$routeCollector->addRoute([Router::GET], '/test', 'TestModuleClassName');

		$router->getModuleClass('/test');
	}
	
	public function testPostModuleClassMethodNotAllowed()
	{
		$this->expectException(MethodNotAllowedException::class);

		$router = new Router(['REQUEST_METHOD' => Router::GET], $this->l10n);

		$routeCollector = $router->getRouteCollector();
		$routeCollector->addRoute([Router::POST], '/test', 'TestModuleClassName');

		$router->getModuleClass('/test');
	}

	public function dataRoutes()
	{
		return [
			'default' => [
				'routes' => [
					'/'       => [Module\Home::class, [Router::GET]],
					'/group'  => [
						'/route' => [Module\Friendica::class, [Router::GET]],
					],


					'/group2' => [
						'/group3' => [
							'/route' => [Module\Xrd::class, [Router::GET]],
						],
					],
					'/post' => [
						'/it' => [Module\NodeInfo::class, [Router::POST]],
					],
					'/double' => [Module\Profile\Index::class, [Router::GET, Router::POST]]
				],
			],
		];
	}

	/**
	 * @dataProvider dataRoutes
	 */
	public function testGetRoutes(array $routes)
	{
		$router = (new Router([
			'REQUEST_METHOD' => Router::GET
		], $this->l10n))->loadRoutes($routes);

		$this->assertEquals(Module\Home::class, $router->getModuleClass('/'));
		$this->assertEquals(Module\Friendica::class, $router->getModuleClass('/group/route'));
		$this->assertEquals(Module\Xrd::class, $router->getModuleClass('/group2/group3/route'));
		$this->assertEquals(Module\Profile\Index::class, $router->getModuleClass('/double'));
	}

	/**
	 * @dataProvider dataRoutes
	 */
	public function testPostRouter(array $routes)
	{
		$router = (new Router([
			'REQUEST_METHOD' => Router::POST
		], $this->l10n))->loadRoutes($routes);

		// Don't find GET
		$this->assertEquals(Module\NodeInfo::class, $router->getModuleClass('/post/it'));
		$this->assertEquals(Module\Profile\Index::class, $router->getModuleClass('/double'));
	}
}
