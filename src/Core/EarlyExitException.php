<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Core;

use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 *
 * Carries a ResponseInterface out of the dispatch flow to be returned by handleRequest().
 * Replaces System::exit() for early-terminating modules.
 *
 * Modules can throw this exception to terminate request processing early
 * and provide a response directly, instead of calling httpExit() or similar.
 * The exception is caught by App::runFrontend() and BaseModule::run().
 */
class EarlyExitException extends \RuntimeException
{
	public function __construct(
		private readonly ResponseInterface $response,
	) {
		parent::__construct('Module requested early exit');
	}

	public function getResponse(): ResponseInterface
	{
		return $this->response;
	}
}
