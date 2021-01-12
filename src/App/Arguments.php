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

namespace Friendica\App;

/**
 * Determine all arguments of the current call, including
 * - The whole querystring (except the pagename/q parameter)
 * - The command
 * - The arguments (C-Style based)
 * - The count of arguments
 */
class Arguments
{
	/**
	 * @var string The complete query string
	 */
	private $queryString;
	/**
	 * @var string The current Friendica command
	 */
	private $command;
	/**
	 * @var array The arguments of the current execution
	 */
	private $argv;
	/**
	 * @var int The count of arguments
	 */
	private $argc;

	public function __construct(string $queryString = '', string $command = '', array $argv = [], int $argc = 0)
	{
		$this->queryString = $queryString;
		$this->command     = $command;
		$this->argv        = $argv;
		$this->argc        = $argc;
	}

	/**
	 * @return string The whole query string of this call with url-encoded query parameters
	 */
	public function getQueryString()
	{
		return $this->queryString;
	}

	/**
	 * @return string The whole command of this call
	 */
	public function getCommand()
	{
		return $this->command;
	}

	/**
	 * @return array All arguments of this call
	 */
	public function getArgv()
	{
		return $this->argv;
	}

	/**
	 * @return int The count of arguments of this call
	 */
	public function getArgc()
	{
		return $this->argc;
	}

	/**
	 * Returns the value of a argv key
	 * @todo there are a lot of $a->argv usages in combination with ?? which can be replaced with this method
	 *
	 * @param int   $position the position of the argument
	 * @param mixed $default  the default value if not found
	 *
	 * @return mixed returns the value of the argument
	 */
	public function get(int $position, $default = '')
	{
		return $this->has($position) ? $this->argv[$position] : $default;
	}

	/**
	 * @param int $position
	 *
	 * @return bool if the argument position exists
	 */
	public function has(int $position)
	{
		return array_key_exists($position, $this->argv);
	}

	/**
	 * Determine the arguments of the current call
	 *
	 * @param array $server The $_SERVER variable
	 * @param array $get    The $_GET variable
	 *
	 * @return Arguments The determined arguments
	 */
	public function determine(array $server, array $get)
	{
		// removing leading / - maybe a nginx problem
		$server['QUERY_STRING'] = ltrim($server['QUERY_STRING'] ?? '', '/');

		$queryParameters = [];
		parse_str($server['QUERY_STRING'], $queryParameters);

		if (!empty($get['pagename'])) {
			$command = trim($get['pagename'], '/\\');
		} elseif (!empty($queryParameters['pagename'])) {
			$command = trim($queryParameters['pagename'], '/\\');
		} elseif (!empty($get['q'])) {
			// Legacy page name parameter, now conflicts with the search query parameter
			$command = trim($get['q'], '/\\');
		} else {
			$command = '';
		}

		// Remove generated and one-time use parameters
		unset($queryParameters['pagename']);
		unset($queryParameters['zrl']);
		unset($queryParameters['owt']);

		/*
		 * Break the URL path into C style argc/argv style arguments for our
		 * modules. Given "http://example.com/module/arg1/arg2", $this->argc
		 * will be 3 (integer) and $this->argv will contain:
		 *   [0] => 'module'
		 *   [1] => 'arg1'
		 *   [2] => 'arg2'
		 */
		if ($command) {
			$argv = explode('/', $command);
		} else {
			$argv = [];
		}

		$argc = count($argv);

		$queryString = $command . ($queryParameters ? '?' . http_build_query($queryParameters) : '');

		return new Arguments($queryString, $command, $argv, $argc);
	}
}
