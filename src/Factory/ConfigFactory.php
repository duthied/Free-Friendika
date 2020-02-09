<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Factory;

use Exception;
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
	 *
	 * @throws Exception
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
