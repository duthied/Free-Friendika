<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Util\Router;

use FastRoute\Dispatcher\GroupCountBased;

/**
 * Extends the Fast-Router collector for getting the possible HTTP method options for a given URI
 */
class FriendicaGroupCountBased extends GroupCountBased
{
	/**
	 * Returns all possible HTTP methods for a given URI
	 *
	 * @param $uri
	 *
	 * @return array
	 *
	 * @todo Distinguish between an invalid route and the asterisk (*) default route
	 */
	public function getOptions($uri): array
	{
		$varRouteData = $this->variableRouteData;

		// Find allowed methods for this URI by matching against all other HTTP methods as well
		$allowedMethods = [];

		foreach ($this->staticRouteMap as $method => $uriMap) {
			if (isset($uriMap[$uri])) {
				$allowedMethods[] = $method;
			}
		}

		foreach ($varRouteData as $method => $routeData) {
			$result = $this->dispatchVariableRoute($routeData, $uri);
			if ($result[0] === self::FOUND) {
				$allowedMethods[] = $method;
			}
		}

		return $allowedMethods;
	}
}
