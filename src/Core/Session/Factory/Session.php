<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Session\Factory;

use Friendica\App\BaseURL;
use Friendica\App\Mode;
use Friendica\Core\Cache\Factory\Cache;
use Friendica\Core\Cache\Type\DatabaseCache;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Session\Capability\IHandleSessions;
use Friendica\Core\Session\Handler\Cache as CacheHandler;
use Friendica\Core\Session\Handler\Database as DatabaseHandler;
use Friendica\Core\Session\Type\Memory;
use Friendica\Core\Session\Type\Native;
use Friendica\Database\Database;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Factory for creating a valid Session for this run
 */
class Session
{
	/** @var string The plain, PHP internal session management */
	public const HANDLER_NATIVE = 'native';
	/** @var string Using the database for session management */
	public const HANDLER_DATABASE = 'database';
	/** @var string Using the cache for session management */
	public const HANDLER_CACHE = 'cache';

	public const HANDLER_DEFAULT = self::HANDLER_DATABASE;

	/**
	 * @param Mode                $mode
	 * @param BaseURL             $baseURL
	 * @param IManageConfigValues $config
	 * @param Database            $dba
	 * @param Cache               $cacheFactory
	 * @param LoggerInterface     $logger
	 * @param Profiler            $profiler
	 * @param array               $server
	 * @return IHandleSessions
	 */
	public function create(Mode $mode, BaseURL $baseURL, IManageConfigValues $config, Database $dba, Cache $cacheFactory, LoggerInterface $logger, Profiler $profiler, array $server = []): IHandleSessions
	{
		$profiler->startRecording('session');
		$session_handler = $config->get('system', 'session_handler', self::HANDLER_DEFAULT);

		if ($mode->isInstall() || $mode->isBackend()) {
			$session = new Memory();
			$profiler->stopRecording();
			return $session;
		}

		try {
			switch ($session_handler) {
				case self::HANDLER_DATABASE:
					$handler = new DatabaseHandler($dba, $logger, $server);
					break;
				case self::HANDLER_CACHE:
					$cache = $cacheFactory->createDistributed();

					// In case we're using the db as cache driver, use the native db session, not the cache
					if ($config->get('system', 'cache_driver') === DatabaseCache::NAME) {
						$handler = new DatabaseHandler($dba, $logger, $server);
					} else {
						$handler = new CacheHandler($cache, $logger);
					}
					break;
				default:
					$handler = null;
			}
		} catch (Throwable $e) {
			$logger->notice('Unable to create session', ['mode' => $mode, 'session_handler' => $session_handler, 'exception' => $e]);
			$session = new Memory();
			$profiler->stopRecording();
			return $session;
		}

		try {
			$session = new Native($baseURL, $handler);
		} catch (Throwable $e) {
			$logger->notice('Unable to create session', ['mode' => $mode, 'session_handler' => $session_handler, 'exception' => $e]);
			$session = new Memory();
		}

		$profiler->stopRecording();
		return $session;
	}
}
