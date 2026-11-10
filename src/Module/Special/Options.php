<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Special;

use Friendica\App\Router;
use Friendica\BaseModule;
use Friendica\Module\Response;

/**
 * Returns the allowed HTTP methods based on the route information
 *
 * It's a special class which shouldn't be called directly
 *
 * @see Router::getModuleClass()
 */
class Options extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$allowedMethods = $this->parameters['AllowedMethods'] ?? Router::ALLOWED_METHODS;

		// @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/OPTIONS
		$this->response->setHeader(implode(',', $allowedMethods), 'Allow');
		$this->response->setStatus(204);
		$this->response->setType(Response::TYPE_BLANK);
	}
}
