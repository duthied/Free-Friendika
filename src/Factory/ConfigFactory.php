<?php

namespace Friendica\Factory;

use Friendica\Core;
use Friendica\Core\Config;
use Friendica\Core\Config\Cache;
use Friendica\Model\Config\Config as ConfigModel;
use Friendica\Model\Config\PConfig as PConfigModel;
use Friendica\Util\Config\ConfigFileLoader;

class ConfigFactory
{
	/**
	 * @param ConfigFileLoader $loader The Config Cache loader (INI/config/.htconfig)
	 *
	 * @return Cache\ConfigCache
	 */
	public static function createCache(ConfigFileLoader $loader)
	{
		$configCache = new Cache\ConfigCache();
		$loader->setupCache($configCache);

		return $configCache;
	}

	/**
	 * @param Cache\ConfigCache $configCache The config cache of this adapter
	 * @param ConfigModel $configModel The configuration model
	 *
	 * @return Config\Configuration
	 */
	public static function createConfig(Cache\ConfigCache $configCache, ConfigModel $configModel)
	{
		if ($configCache->get('system', 'config_adapter') === 'preload') {
			$configuration = new Config\PreloadConfiguration($configCache, $configModel);
		} else {
			$configuration = new Config\JitConfiguration($configCache, $configModel);
		}


		// Set the config in the static container for legacy usage
		Core\Config::init($configuration);

		return $configuration;
	}

	/**
	 * @param Cache\ConfigCache $configCache The config cache
	 * @param Cache\PConfigCache  $pConfigCache The personal config cache
	 * @param PConfigModel $configModel The configuration model
	 *
	 * @return Config\PConfiguration
	 */
	public static function createPConfig(Cache\ConfigCache $configCache, Cache\PConfigCache $pConfigCache, PConfigModel $configModel)
	{
		if ($configCache->get('system', 'config_adapter') === 'preload') {
			$configuration = new Config\PreloadPConfiguration($pConfigCache, $configModel);
		} else {
			$configuration = new Config\JitPConfiguration($pConfigCache, $configModel);
		}

		// Set the config in the static container for legacy usage
		Core\PConfig::init($configuration);

		return $configuration;
	}
}
