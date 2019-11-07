<?php

namespace Friendica\App;


use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Network\HTTPException;

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
	const POST = 'POST';
	const GET  = 'GET';

	const ALLOWED_METHODS = [
		self::POST,
		self::GET,
	];

	/** @var RouteCollector */
	protected $routeCollector;

	/**
	 * @var string The HTTP method
	 */
	private $httpMethod;

	/**
	 * @var array Module parameters
	 */
	private $parameters = [];

	/**
	 * @param array $server The $_SERVER variable
	 * @param RouteCollector|null $routeCollector Optional the loaded Route collector
	 */
	public function __construct(array $server, RouteCollector $routeCollector = null)
	{
		$httpMethod = $server['REQUEST_METHOD'] ?? self::GET;
		$this->httpMethod = in_array($httpMethod, self::ALLOWED_METHODS) ? $httpMethod : self::GET;

		$this->routeCollector = isset($routeCollector) ?
			$routeCollector :
			new RouteCollector(new Std(), new GroupCountBased());
	}

	/**
	 * @param array $routes The routes to add to the Router
	 *
	 * @return self The router instance with the loaded routes
	 *
	 * @throws HTTPException\InternalServerErrorException In case of invalid configs
	 */
	public function loadRoutes(array $routes)
	{
		$routeCollector = (isset($this->routeCollector) ?
			$this->routeCollector :
			new RouteCollector(new Std(), new GroupCountBased()));

		$this->addRoutes($routeCollector, $routes);

		$this->routeCollector = $routeCollector;

		return $this;
	}

	private function addRoutes(RouteCollector $routeCollector, array $routes)
	{
		foreach ($routes as $route => $config) {
			if ($this->isGroup($config)) {
				$this->addGroup($route, $config, $routeCollector);
			} elseif ($this->isRoute($config)) {
				$routeCollector->addRoute($config[1], $route, $config[0]);
			} else {
				throw new HTTPException\InternalServerErrorException("Wrong route config for route '" . print_r($route, true) . "'");
			}
		}
	}

	/**
	 * Adds a group of routes to a given group
	 *
	 * @param string         $groupRoute     The route of the group
	 * @param array          $routes         The routes of the group
	 * @param RouteCollector $routeCollector The route collector to add this group
	 */
	private function addGroup(string $groupRoute, array $routes, RouteCollector $routeCollector)
	{
		$routeCollector->addGroup($groupRoute, function (RouteCollector $routeCollector) use ($routes) {
			$this->addRoutes($routeCollector, $routes);
		});
	}

	/**
	 * Returns true in case the config is a group config
	 *
	 * @param array $config
	 *
	 * @return bool
	 */
	private function isGroup(array $config)
	{
		return
			is_array($config) &&
			is_string(array_keys($config)[0]) &&
			// This entry should NOT be a BaseModule
			(substr(array_keys($config)[0], 0, strlen('Friendica\Module')) !== 'Friendica\Module') &&
			// The second argument is an array (another routes)
			is_array(array_values($config)[0]);
	}

	/**
	 * Returns true in case the config is a route config
	 *
	 * @param array $config
	 *
	 * @return bool
	 */
	private function isRoute(array $config)
	{
		return
			// The config array should at least have one entry
			!empty($config[0]) &&
			// This entry should be a BaseModule
			(substr($config[0], 0, strlen('Friendica\Module')) === 'Friendica\Module') &&
			// Either there is no other argument
			(empty($config[1]) ||
			 // Or the second argument is an array (HTTP-Methods)
			 is_array($config[1]));
	}

	/**
	 * The current route collector
	 *
	 * @return RouteCollector|null
	 */
	public function getRouteCollector()
	{
		return $this->routeCollector;
	}

	/**
	 * Returns the relevant module class name for the given page URI or NULL if no route rule matched.
	 *
	 * @param string $cmd The path component of the request URL without the query string
	 *
	 * @return string A Friendica\BaseModule-extending class name if a route rule matched
	 *
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\MethodNotAllowedException    If a rule matched but the method didn't
	 * @throws HTTPException\NotFoundException            If no rule matched
	 */
	public function getModuleClass($cmd)
	{
		// Add routes from addons
		Hook::callAll('route_collection', $this->routeCollector);

		$cmd = '/' . ltrim($cmd, '/');

		$dispatcher = new Dispatcher\GroupCountBased($this->routeCollector->getData());

		$moduleClass = null;
		$this->parameters = [];

		$routeInfo  = $dispatcher->dispatch($this->httpMethod, $cmd);
		if ($routeInfo[0] === Dispatcher::FOUND) {
			$moduleClass = $routeInfo[1];
			$this->parameters = $routeInfo[2];
		} elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
			throw new HTTPException\MethodNotAllowedException(L10n::t('Method not allowed for this module. Allowed method(s): %s', implode(', ', $routeInfo[1])));
		} else {
			throw new HTTPException\NotFoundException(L10n::t('Page not found.'));
		}

		return $moduleClass;
	}

	/**
	 * Returns the module parameters.
	 *
	 * @return array parameters
	 */
	public function getModuleParameters()
	{
		return $this->parameters;
	}
}
