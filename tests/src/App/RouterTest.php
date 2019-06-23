<?php

namespace Friendica\Test\src\App;

use Friendica\App\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
	public function testGetModuleClass()
	{
		$router = new Router();

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
}
