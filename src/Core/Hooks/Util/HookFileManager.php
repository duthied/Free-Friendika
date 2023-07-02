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

use Friendica\Core\Addon\Capabilities\ICanLoadAddons;
use Friendica\Core\Hooks\Capabilities\HookType;
use Friendica\Core\Hooks\Capabilities\ICanRegisterInstances;
use Friendica\Core\Hooks\Exceptions\HookConfigException;

/**
 * Manage all hooks.config.php files
 */
class HookFileManager
{
	const STATIC_DIR = 'static';
	const CONFIG_NAME = 'hooks';

	/** @var ICanLoadAddons */
	protected $addonLoader;
	/** @var array */
	protected $hookConfig = [];
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
	 * @param ICanRegisterInstances $instanceRegister The instance register
	 *
	 * @return void
	 */
	public function setupHooks(ICanRegisterInstances $instanceRegister)
	{
		// In case it wasn't used before, reload the whole hook config
		if (empty($this->hookConfig)) {
			$this->reloadHookConfig();
		}

		foreach ($this->hookConfig as $hookType => $classList) {
			switch ($hookType) {
				case HookType::STRATEGY:
					foreach ($classList as $interface => $strategy) {
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
					break;
				case HookType::DECORATOR:
					foreach ($classList as $interface => $decorators) {
						if (is_array($decorators)) {
							foreach ($decorators as $decorator) {
								$instanceRegister->registerDecorator($interface, $decorator);
							}
						} else {
							$instanceRegister->registerDecorator($interface, $decorators);
						}
					}
					break;
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
	protected function reloadHookConfig()
	{
		// load core hook config
		$configFile = $this->basePath . '/' . static::STATIC_DIR . '/' . static::CONFIG_NAME . '.config.php';

		if (!file_exists($configFile)) {
			throw new HookConfigException(sprintf('config file %s does not exit.', $configFile));
		}

		$config = include $configFile;

		if (!is_array($config)) {
			throw new HookConfigException('Error loading config file ' . $configFile);
		}

		$this->hookConfig = array_merge_recursive($config, $this->addonLoader->getActiveAddonConfig(static::CONFIG_NAME));
	}
}
