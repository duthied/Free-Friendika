<?php

namespace Friendica\Test\src\App;

use Friendica\App\Router;
use Friendica\Module;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
	public function testGetModuleClass()
	{
		$router = new Router(['GET']);

		$routeCollector = $router->getRouteCollector();
		$routeCollector->addRoute(['GET'], '/', 'IndexModuleClassName');
		$routeCollector->addRoute(['GET'], '/test', 'TestModuleClassName');
		$routeCollector->addRoute(['GET'], '/test/sub', 'TestSubModuleClassName');
		$routeCollector->addRoute(['GET'], '/optional[/option]', 'OptionalModuleClassName');
		$routeCollector->addRoute(['GET'], '/variable/{var}', 'VariableModuleClassName');
		$routeCollector->addRoute(['GET'], '/optionalvariable[/{option}]', 'OptionalVariableModuleClassName');
		$routeCollector->addRoute(['POST', 'PUT', 'PATCH', 'DELETE', 'HEAD'], '/unsupported', 'UnsupportedMethodModuleClassName');

		$this->assertEquals('IndexModuleClassName', $router->getModuleClass('/'));

		$this->assertEquals('TestModuleClassName', $router->getModuleClass('/test'));
		$this->assertNull($router->getModuleClass('/tes'));

		$this->assertEquals('TestSubModuleClassName', $router->getModuleClass('/test/sub'));

		$this->assertEquals('OptionalModuleClassName', $router->getModuleClass('/optional'));
		$this->assertEquals('OptionalModuleClassName', $router->getModuleClass('/optional/option'));
		$this->assertNull($router->getModuleClass('/optional/opt'));

		$this->assertEquals('VariableModuleClassName', $router->getModuleClass('/variable/123abc'));
		$this->assertNull($router->getModuleClass('/variable'));

		$this->assertEquals('OptionalVariableModuleClassName', $router->getModuleClass('/optionalvariable'));
		$this->assertEquals('OptionalVariableModuleClassName', $router->getModuleClass('/optionalvariable/123abc'));

		$this->assertNull($router->getModuleClass('/unsupported'));
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
					'/double' => [Module\Profile::class, [Router::GET, Router::POST]]
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
		]))->addRoutes($routes);

		$this->assertEquals(Module\Home::class, $router->getModuleClass('/'));
		$this->assertEquals(Module\Friendica::class, $router->getModuleClass('/group/route'));
		$this->assertEquals(Module\Xrd::class, $router->getModuleClass('/group2/group3/route'));
		$this->assertNull($router->getModuleClass('/post/it'));
		$this->assertEquals(Module\Profile::class, $router->getModuleClass('/double'));
	}

	/**
	 * @dataProvider dataRoutes
	 */
	public function testPostRouter(array $routes)
	{
		$router = (new Router([
			'REQUEST_METHOD' => Router::POST
		]))->addRoutes($routes);

		// Don't find GET
		$this->assertNull($router->getModuleClass('/'));
		$this->assertNull($router->getModuleClass('/group/route'));
		$this->assertNull($router->getModuleClass('/group2/group3/route'));
		$this->assertEquals(Module\NodeInfo::class, $router->getModuleClass('/post/it'));
		$this->assertEquals(Module\Profile::class, $router->getModuleClass('/double'));
	}
}
