<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\HTTPException;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Module\Response;
use Friendica\Module\Special\HTTPException as ModuleHTTPException;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class PageNotFound extends BaseModule
{
	/** @var string */
	private $remoteAddress;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, App\Request $request, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->remoteAddress = $request->getRemoteAddress();
	}

	protected function content(array $request = []): string
	{
		throw new HTTPException\NotFoundException($this->t('Page not found.'));
	}

	public function run(ModuleHTTPException $httpException, array $request = []): ResponseInterface
	{
		$this->preProcessServerVars($this->server);

		return parent::run($httpException, $request);
	}

	public function handleRequest(ServerRequestInterface $request): ResponseInterface
	{
		$this->preProcessServerVars($request->getServerParams());

		return parent::handleRequest($request);
	}

	/**
	 * Check for JS template prefetch and log the 404 request
	 */
	private function preProcessServerVars(array $server): void
	{
		// The URL provided does not resolve to a valid module.
		$queryString = $server['QUERY_STRING'] ?? '';
		// Stupid browser tried to pre-fetch our JavaScript img template. Don't log the event or return anything - just quietly exit.
		if (!empty($queryString) && preg_match('/{[0-9]}/', (string) $queryString) !== 0) {
			System::exit();
		}

		$this->logger->debug('index.php: page not found.', [
			'request_uri' => $server['REQUEST_URI'] ?? '',
			'address'     => $this->remoteAddress,
			'query'       => $server['QUERY_STRING'] ?? '',
		]);
	}
}
