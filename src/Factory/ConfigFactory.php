<?php

namespace Friendica\Factory;

use Friendica\Core;
use Friendica\Core\Config;
use Friendica\Core\Config\Adapter;
use Friendica\Core\Config\Cache;
use Friendica\Model\Config\Config as ConfigModel;
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
	 * @param int                $uid         The UID of the current user
	 *
	 * @return Config\PConfiguration
	 */
	public static function createPConfig(Cache\ConfigCache $configCache, Cache\PConfigCache $pConfigCache, $uid = null)
	{
		if ($configCache->get('system', 'config_adapter') === 'preload') {
			$configAdapter = new Adapter\PreloadPConfigAdapter($uid);
		} else {
			$configAdapter = new Adapter\JITPConfigAdapter();
		}

		$configuration = new Config\PConfiguration($pConfigCache, $configAdapter);

		// Set the config in the static container for legacy usage
		Core\PConfig::init($configuration);

		return $configuration;
	}
}
