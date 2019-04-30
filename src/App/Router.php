<?php

namespace Friendica\App;


use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Friendica\Module;

/**
 * Wrapper for FastRoute\Router
 *
 * This wrapper only makes use of a subset of the router features, mainly parses a route rule to return the relevant
 * module class.
 *
 * Actual routes are defined in App->collectRoutes.
 *
 * @package Friendica\App
 */
class Router
{
	/** @var RouteCollector */
	protected $routeCollector;

	/**
	 * Static declaration of Friendica routes.
	 *
	 * Supports:
	 * - Route groups
	 * - Variable parts
	 * Disregards:
	 * - HTTP method other than GET
	 * - Named parameters
	 *
	 * Handler must be the name of a class extending Friendica\BaseModule.
	 *
	 * @brief Static declaration of Friendica routes.
	 */
	public function collectRoutes()
	{
		$this->routeCollector->addRoute(['GET', 'POST'], '/itemsource[/{guid}]', Module\Itemsource::class);
		$this->routeCollector->addRoute(['GET'],         '/amcd',                Module\AccountManagementControlDocument::class);
		$this->routeCollector->addRoute(['GET'],         '/host-meta',           Module\HostMeta::class);
		$this->routeCollector->addRoute(['GET'],         '/hostxrd',             Module\HostMeta::class);
		$this->routeCollector->addRoute(['GET'],         '/nodeinfo/1.0',        Module\NodeInfo::class);
		$this->routeCollector->addRoute(['GET'],         '/xrd',                 Module\WebFinger::class);
		$this->routeCollector->addGroup('/.well-known', function (RouteCollector $collector) {
			$collector->addRoute(['GET'], '/host-meta'       , Module\WellKnown\HostMeta::class);
			$collector->addRoute(['GET'], '/nodeinfo[/1.0]'  , Module\NodeInfo::class);
			$collector->addRoute(['GET'], '/webfinger'       , Module\WebFinger::class);
			$collector->addRoute(['GET'], '/x-social-relay'  , Module\WellKnown\XSocialRelay::class);
		});
	}

	public function __construct(RouteCollector $routeCollector = null)
	{
		if (!$routeCollector) {
			$routeCollector = new RouteCollector(new Std(), new GroupCountBased());
		}

		$this->routeCollector = $routeCollector;
	}

	public function getRouteCollector()
	{
		return $this->routeCollector;
	}

	/**
	 * Returns the relevant module class name for the given page URI or NULL if no route rule matched.
	 *
	 * @param string $cmd The path component of the request URL without the query string
	 * @return string|null A Friendica\BaseModule-extending class name if a route rule matched
	 */
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
