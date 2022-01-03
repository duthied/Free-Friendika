<?php

namespace Friendica\Test\src\Util\Router;

use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Friendica\Module\Special\Options;
use Friendica\Test\MockedTest;
use Friendica\Util\Router\FriendicaGroupCountBased;

class FriendicaGroupCountBasedTest extends MockedTest
{
	public function testOptions()
	{
		$collector = new RouteCollector(new Std(), new GroupCountBased());
		$collector->addRoute('GET', '/get', Options::class);
		$collector->addRoute('POST', '/post', Options::class);
		$collector->addRoute('GET', '/multi', Options::class);
		$collector->addRoute('POST', '/multi', Options::class);

		$dispatcher = new FriendicaGroupCountBased($collector->getData());

		self::assertEquals(['GET'], $dispatcher->getOptions('/get'));
		self::assertEquals(['POST'], $dispatcher->getOptions('/post'));
		self::assertEquals(['GET', 'POST'], $dispatcher->getOptions('/multi'));
	}
}
