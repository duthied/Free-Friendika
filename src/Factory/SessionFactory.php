<?php

namespace Friendica\Factory;

use Friendica\App;
use Friendica\Core\Cache\Cache;
use Friendica\Core\Cache\ICache;
use Friendica\Core\Config\Configuration;
use Friendica\Core\Session\Cache;
use Friendica\Core\Session\Database;
use Friendica\Core\Session\ISession;
use Friendica\Core\Session\Memory;
use Friendica\Core\Session\Memory;
use Friendica\Core\Session\Native;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Model\User\Cookie;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating a valid Session for this run
 */
class SessionFactory
{
	/** @var string The plain, PHP internal session management */
	const INTERNAL = 'native';
	/** @var string Using the database for session management */
	const DATABASE = 'database';
	/** @var string Using the cache for session management */
	const CACHE = 'cache';
	/** @var string A temporary cached session */
	const MEMORY  = 'memory';
	/** @var string The default type for Session management in case of no config */
	const DEFAULT = self::DATABASE;

	/**
	 * @param App\Mode        $mode
	 * @param Configuration   $config
	 * @param Cookie          $cookie
	 * @param Database        $dba
	 * @param ICache          $cache
	 * @param LoggerInterface $logger
	 * @param array           $server
	 *
	 * @return ISession
	 */
	public function createSession(App\Mode $mode, Configuration $config, Cookie $cookie, Database $dba, ICache $cache, LoggerInterface $logger, Profiler $profiler, array $server = [])
	{
		$stamp1  = microtime(true);
		$session = null;

		try {
			if ($mode->isInstall() || $mode->isBackend()) {
				$session = new Memory();
			} else {
				$session_handler = $config->get('system', 'session_handler', self::DEFAULT);

				switch ($session_handler) {
					case self::INTERNAL:
						$session = new Native($config, $cookie);
						break;
					case self::DATABASE:
					default:
						$session = new Database($config, $cookie, $dba, $logger, $server);
						break;
					case self::CACHE:
						// In case we're using the db as cache driver, use the native db session, not the cache
						if ($config->get('system', 'cache_driver') === Cache::TYPE_DATABASE) {
							$session = new Database($config, $cookie, $dba, $logger, $server);
						} else {
							$session = new Cache($config, $cookie, $cache, $logger, $server);
						}
						break;
				}
			}
		} finally {
			$profiler->saveTimestamp($stamp1, 'parser', System::callstack());
			return $session;
		}
	}
}
