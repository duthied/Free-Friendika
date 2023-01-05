<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Core\Config\Model;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Config\Capability\ISetConfigValuesTransactionally;
use Friendica\Core\Config\Exception\ConfigFileException;
use Friendica\Core\Config\Exception\ConfigPersistenceException;
use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Core\Config\ValueObject\Cache;

/**
 * Configuration model, which manages the whole system configuration
 */
class Config implements IManageConfigValues
{
	/** @var Cache */
	protected $configCache;

	/** @var ConfigFileManager */
	protected $configFileManager;

	/**
	 * @param ConfigFileManager $configFileManager The configuration file manager to save back configs
	 * @param Cache             $configCache       The configuration cache (based on the config-files)
	 */
	public function __construct(ConfigFileManager $configFileManager, Cache $configCache)
	{
		$this->configFileManager = $configFileManager;
		$this->configCache       = $configCache;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getCache(): Cache
	{
		return $this->configCache;
	}

	/**	{@inheritDoc} */
	public function beginTransaction(): ISetConfigValuesTransactionally
	{
		return new ConfigTransaction($this);
	}

	/**
	 * Saves the current Configuration back into the data config.
	 * @see ConfigFileManager::CONFIG_DATA_FILE
	 */
	protected function save()
	{
		try {
			$this->configFileManager->saveData($this->configCache);
		} catch (ConfigFileException $e) {
			throw new ConfigPersistenceException('Cannot save config', $e);
		}
	}

	/** {@inheritDoc} */
	public function reload()
	{
		$configCache = new Cache();

		try {
			$this->configFileManager->setupCache($configCache);
		} catch (ConfigFileException $e) {
			throw new ConfigPersistenceException('Cannot reload config', $e);
		}
		$this->configCache = $configCache;
	}

	/** {@inheritDoc} */
	public function load(Cache $cache)
	{
		$this->configCache = $cache;
		$this->save();
	}

	/** {@inheritDoc} */
	public function get(string $cat, string $key = null, $default_value = null)
	{
		return $this->configCache->get($cat, $key) ?? $default_value;
	}

	/** {@inheritDoc} */
	public function set(string $cat, string $key, $value): bool
	{
		if ($this->configCache->set($cat, $key, $value, Cache::SOURCE_DATA)) {
			$this->save();
			return true;
		} else {
			return false;
		}
	}

	/** {@inheritDoc} */
	public function delete(string $cat, string $key): bool
	{
		if ($this->configCache->delete($cat, $key)) {
			$this->save();
			return true;
		} else {
			return false;
		}
	}
}
