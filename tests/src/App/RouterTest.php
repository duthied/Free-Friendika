<?php

namespace Friendica\Test\src\App;

use Friendica\App\Router;
use Friendica\Module;
use Friendica\Network\HTTPException\MethodNotAllowedException;
use Friendica\Network\HTTPException\NotFoundException;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
	public function testGetModuleClass()
	{
		$router = new Router(['REQUEST_METHOD' => 'GET']);

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

		$router = new Router(['REQUEST_METHOD' => 'GET']);

		$router->getModuleClass('/unsupported');
	}

	public function testGetModuleClassNotFoundTypo()
	{
		$this->expectException(NotFoundException::class);

		$router = new Router(['REQUEST_METHOD' => 'GET']);

		$routeCollector = $router->getRouteCollector();
		$routeCollector->addRoute(['GET'], '/test', 'TestModuleClassName');

		$router->getModuleClass('/tes');
	}

	public function testGetModuleClassNotFoundOptional()
	{
		$this->expectException(NotFoundException::class);

		$router = new Router(['REQUEST_METHOD' => 'GET']);

		$routeCollector = $router->getRouteCollector();
		$routeCollector->addRoute(['GET'], '/optional[/option]', 'OptionalModuleClassName');

		$router->getModuleClass('/optional/opt');
	}

	public function testGetModuleClassNotFoundVariable()
	{
		$this->expectException(NotFoundException::class);

		$router = new Router(['REQUEST_METHOD' => 'GET']);

		$routeCollector = $router->getRouteCollector();
		$routeCollector->addRoute(['GET'], '/variable/{var}', 'VariableModuleClassName');

		$router->getModuleClass('/variable');
	}

	public function testGetModuleClassMethodNotAllowed()
	{
		$this->expectException(MethodNotAllowedException::class);

		$router = new Router(['REQUEST_METHOD' => 'POST']);

		$routeCollector = $router->getRouteCollector();
		$routeCollector->addRoute(['GET'], '/test', 'TestModuleClassName');

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
