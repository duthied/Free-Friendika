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

namespace Friendica\Core\Hooks\Util;

use Friendica\Core\Addon\Capability\ICanLoadAddons;
use Friendica\Core\Hooks\Capability\ICanRegisterStrategies;
use Friendica\Core\Hooks\Exceptions\HookConfigException;

/**
 * Manage all strategies.config.php files
 */
class StrategiesFileManager
{
	/**
	 * The default hook-file-key of strategies
	 * -> it's an empty string to cover empty/missing config values
	 */
	const STRATEGY_DEFAULT_KEY = '';
	const STATIC_DIR  = 'static';
	const CONFIG_NAME = 'strategies';

	/** @var ICanLoadAddons */
	protected $addonLoader;
	/** @var array */
	protected $config = [];
	/** @var string */
	protected $basePath;

	public function __construct(string $basePath, ICanLoadAddons $addonLoader)
	{
		$this->basePath    = $basePath;
		$this->addonLoader = $addonLoader;
	}

	/**
	 * Loads all kinds of hooks and registers the corresponding instances
	 *
	 * @param ICanRegisterStrategies $instanceRegister The instance register
	 *
	 * @return void
	 */
	public function setupStrategies(ICanRegisterStrategies $instanceRegister)
	{
		foreach ($this->config as $interface => $strategy) {
			foreach ($strategy as $dependencyName => $names) {
				if (is_array($names)) {
					foreach ($names as $name) {
						$instanceRegister->registerStrategy($interface, $dependencyName, $name);
					}
				} else {
					$instanceRegister->registerStrategy($interface, $dependencyName, $names);
				}
			}
		}
	}

	/**
	 * Reloads all hook config files into the config cache for later usage
	 *
	 * Merges all hook configs from every addon - if present - as well
	 *
	 * @return void
	 */
	public function loadConfig()
	{
		// load core hook config
		$configFile = $this->basePath . '/' . static::STATIC_DIR . '/' . static::CONFIG_NAME . '.config.php';

		if (!file_exists($configFile)) {
			throw new HookConfigException(sprintf('config file %s does not exist.', $configFile));
		}

		$config = include $configFile;

		if (!is_array($config)) {
			throw new HookConfigException(sprintf('Error loading config file %s.', $configFile));
		}

		$this->config = array_merge_recursive($config, $this->addonLoader->getActiveAddonConfig(static::CONFIG_NAME));
	}
}
