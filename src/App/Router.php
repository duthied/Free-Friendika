<?php

namespace Friendica\App;


use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Friendica\Module\Itemsource;

class Router
{
	/** @var RouteCollector */
	public $routeCollector;

	public function __construct(RouteCollector $routeCollector = null)
	{
		if (!$routeCollector) {
			$routeCollector = new RouteCollector(new Std(), new GroupCountBased());
		}

		$this->routeCollector = $routeCollector;
	}

	public function getModuleClass($cmd)
	{
		$cmd = '/' . ltrim($cmd, '/');

		$dispatcher = new \FastRoute\Dispatcher\GroupCountBased($this->routeCollector->getData());

		$moduleClass = null;

		// @TODO: Enable method-specific modules
		$httpMethod = 'GET';
		$routeInfo = $dispatcher->dispatch($httpMethod, $cmd);
		if ($routeInfo[0] === Dispatcher::FOUND) {
			$moduleClass = $routeInfo[1];
		}

		return $moduleClass;
	}
}