<?php

namespace Friendica\Factory;

use Friendica\Core\Cache\ICacheDriver;
use Friendica\Core\Cache\IMemoryCacheDriver;
use Friendica\Core\Config\Configuration;
use Friendica\Core\Lock;
use Friendica\Database\Database;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Class LockDriverFactory
 *
 * @package Friendica\Core\Cache
 *
 * A basic class to generate a LockDriver
 */
class LockDriverFactory
{
	/**
	 * @var string The default driver for caching
	 */
	const DEFAULT_DRIVER = 'default';

	/**
	 * @var Configuration The configuration to read parameters out of the config
	 */
	private $config;

	/**
	 * @var Database The database connection in case that the cache is used the dba connection
	 */
	private $dba;

	/**
	 * @var ICacheDriver The memory cache driver in case we use it
	 */
	private $cacheDriver;

	/**
	 * @var Profiler The optional profiler if the cached should be profiled
	 */
	private $profiler;

	/**
	 * @var LoggerInterface The Friendica Logger
	 */
	private $logger;

	public function __construct(ICacheDriver $cacheDriver, Configuration $config, Database $dba, Profiler $profiler, LoggerInterface $logger)
	{
		$this->cacheDriver = $cacheDriver;
		$this->config      = $config;
		$this->dba         = $dba;
		$this->logger      = $logger;
	}

	public function create()
	{
		$lock_driver = $this->config->get('system', 'lock_driver', self::DEFAULT_DRIVER);

		try {
			switch ($lock_driver) {
				case 'memcache':
				case 'memcached':
				case 'redis':
					if ($this->cacheDriver instanceof IMemoryCacheDriver) {
						return new Lock\CacheLockDriver($this->cacheDriver);
					}
					break;

				case 'database':
					return new Lock\DatabaseLockDriver($this->dba);
					break;

				case 'semaphore':
					return new Lock\SemaphoreLockDriver();
					break;

				default:
					return self::useAutoDriver();
			}
		} catch (\Exception $exception) {
			$this->logger->alert('Driver \'' . $lock_driver . '\' failed - Fallback to \'useAutoDriver()\'', ['exception' => $exception]);
			return self::useAutoDriver();
		}
	}

	/**
	 * @brief This method tries to find the best - local - locking method for Friendica
	 *
	 * The following sequence will be tried:
	 * 1. Semaphore Locking
	 * 2. Cache Locking
	 * 3. Database Locking
	 *
	 * @return Lock\ILockDriver
	 */
	private function useAutoDriver()
	{

		// 1. Try to use Semaphores for - local - locking
		if (function_exists('sem_get')) {
			try {
				return new Lock\SemaphoreLockDriver();
			} catch (\Exception $exception) {
				$this->logger->debug('Using Semaphore driver for locking failed.', ['exception' => $exception]);
			}
		}

		// 2. Try to use Cache Locking (don't use the DB-Cache Locking because it works different!)
		$cache_driver = $this->config->get('system', 'cache_driver', 'database');
		if ($cache_driver != 'database') {
			try {
				if ($this->cacheDriver instanceof IMemoryCacheDriver) {
					return new Lock\CacheLockDriver($this->cacheDriver);
				}
			} catch (\Exception $exception) {
				$this->logger->debug('Using Cache driver for locking failed.', ['exception' => $exception]);
			}
		}

		// 3. Use Database Locking as a Fallback
		return new Lock\DatabaseLockDriver($this->dba);
	}
}
