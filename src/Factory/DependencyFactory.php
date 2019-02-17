<?php

namespace Friendica\Factory;

use Friendica\App;
use Friendica\Core\Config\Cache;
use Friendica\Factory;
use Friendica\Util\BasePath;

class DependencyFactory
{
	/**
	 * Setting all default-dependencies of a friendica execution
	 *
	 * @param string $channel   The channel of this execution
	 * @param string $directory The base directory
	 * @param bool   $isBackend True, if it's a backend execution, otherwise false (Default true)
	 *
	 * @return App The application
	 *
	 * @throws \Exception
	 */
	public static function setUp($channel, $directory, $isBackend = true)
	{
		$basedir = BasePath::create($directory, $_SERVER);
		$configLoader = new Cache\ConfigCacheLoader($basedir);
		$configCache = Factory\ConfigFactory::createCache($configLoader);
		$profiler = Factory\ProfilerFactory::create($configCache);
		Factory\DBFactory::init($configCache, $profiler, $_SERVER);
		$config = Factory\ConfigFactory::createConfig($configCache);
		// needed to call PConfig::init()
		Factory\ConfigFactory::createPConfig($configCache);
		$logger = Factory\LoggerFactory::create($channel, $config);

		return new App($config, $logger, $profiler, $isBackend);
	}
}
