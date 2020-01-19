<?php

namespace Friendica\Factory;

use Friendica\Core\Config;
use Friendica\Core\Config\Cache;
use Friendica\Model\Config\Config as ConfigModel;
use Friendica\Model\Config\PConfig as PConfigModel;
use Friendica\Util\ConfigFileLoader;

class ConfigFactory
{
	/**
	 * @param ConfigFileLoader $loader The Config Cache loader (INI/config/.htconfig)
	 *
	 * @return Cache
	 */
	public function createCache(ConfigFileLoader $loader)
	{
		$configCache = new Cache();
		$loader->setupCache($configCache);

		return $configCache;
	}

	/**
	 * @param Cache       $configCache The config cache of this adapter
	 * @param ConfigModel $configModel The configuration model
	 *
	 * @return Config\IConfig
	 */
	public function createConfig(Cache $configCache, ConfigModel $configModel)
	{
		if ($configCache->get('system', 'config_adapter') === 'preload') {
			$configuration = new Config\PreloadConfig($configCache, $configModel);
		} else {
			$configuration = new Config\JitConfig($configCache, $configModel);
		}


		return $configuration;
	}

	/**
	 * @param Cache                         $configCache  The config cache
	 * @param \Friendica\Core\PConfig\Cache $pConfigCache The personal config cache
	 * @param PConfigModel                  $configModel  The configuration model
	 *
	 * @return \Friendica\Core\PConfig\IPConfig
	 */
	public function createPConfig(Cache $configCache, \Friendica\Core\PConfig\Cache $pConfigCache, PConfigModel $configModel)
	{
		if ($configCache->get('system', 'config_adapter') === 'preload') {
			$configuration = new \Friendica\Core\PConfig\PreloadPConfig($pConfigCache, $configModel);
		} else {
			$configuration = new \Friendica\Core\PConfig\JitPConfig($pConfigCache, $configModel);
		}

		return $configuration;
	}
}
