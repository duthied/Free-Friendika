<?php

namespace Friendica\Factory;

use Friendica\Core\Cache;
use Friendica\Core\Cache\ICache;
use Friendica\Core\Config\Configuration;
use Friendica\Database\Database;
use Friendica\Util\BaseURL;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Class CacheDriverFactory
 *
 * @package Friendica\Core\Cache
 *
 * A basic class to generate a CacheDriver
 */
class CacheDriverFactory
{
	/**
	 * @var string The default driver for caching
	 */
	const DEFAULT_DRIVER = 'database';

	/**
	 * @var Configuration The configuration to read parameters out of the config
	 */
	private $config;

	/**
	 * @var Database The database connection in case that the cache is used the dba connection
	 */
	private $dba;

	/**
	 * @var string The hostname, used as Prefix for Caching
	 */
	private $hostname;

	/**
	 * @var Profiler The optional profiler if the cached should be profiled
	 */
	private $profiler;

	/**
	 * @var LoggerInterface The Friendica Logger
	 */
	private $logger;

	public function __construct(BaseURL $baseURL, Configuration $config, Database $dba, Profiler $profiler, LoggerInterface $logger)
	{
		$this->hostname = $baseURL->getHostname();
		$this->config = $config;
		$this->dba = $dba;
		$this->profiler = $profiler;
		$this->logger = $logger;
	}

	/**
	 * This method creates a CacheDriver for the given cache driver name
	 *
	 * @return ICache  The instance of the CacheDriver
	 * @throws \Exception    The exception if something went wrong during the CacheDriver creation
	 */
	public function create()
	{
		$driver = $this->config->get('system', 'cache_driver', self::DEFAULT_DRIVER);

		switch ($driver) {
			case 'memcache':
				$cache = new Cache\MemcacheCache($this->hostname, $this->config);
				break;
			case 'memcached':
				$cache = new Cache\MemcachedCache($this->hostname, $this->config, $this->logger);
				break;
			case 'redis':
				$cache = new Cache\RedisCache($this->hostname, $this->config);
				break;
			case 'apcu':
				$cache = new Cache\APCuCache($this->hostname);
				break;
			default:
				$cache = new Cache\DatabaseCache($this->hostname, $this->dba);
		}

		$profiling = $this->config->get('system', 'profiling', false);

		// In case profiling is enabled, wrap the ProfilerCache around the current cache
		if (isset($profiling) && $profiling !== false) {
			 return new Cache\ProfilerCache($cache, $this->profiler);
		} else {
			return $cache;
		}
	}
}
