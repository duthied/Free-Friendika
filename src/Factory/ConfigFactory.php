<?php

namespace Friendica\Factory;

use Friendica\Core\Config;

class ConfigFactory
{
	/**
	 * @param Config\ConfigCacheLoader $loader The Config Cache loader (INI/config/.htconfig)
	 *
	 * @return Config\ConfigCache
	 */
	public static function createCache(Config\ConfigCacheLoader $loader)
	{
		$configCache = new Config\ConfigCache();
		$loader->loadConfigFiles($configCache);

		return $configCache;
	}

	/**
	 * @param string              $type   The adapter type
	 * @param Config\IConfigCache $config The config cache of this adapter
	 *
	 * @return Config\IConfigAdapter
	 */
	public static function createConfig($type, $config)
	{
		if ($type == 'preload') {
			return new Config\PreloadConfigAdapter($config);
		} else {
			return new Config\JITConfigAdapter($config);
		}
	}

	/**
	 * @param string               $type   The adapter type
	 * @param Config\IPConfigCache $config The config cache of this adapter
	 * @param int                  $uid    The UID of the current user
	 *
	 * @return Config\IPConfigAdapter
	 */
	public static function createPConfig($type, $config, $uid = null)
	{
		if ($type == 'preload') {
			return new Config\PreloadPConfigAdapter($config, $uid);
		} else {
			return new Config\JITPConfigAdapter($config);
		}
	}
}
