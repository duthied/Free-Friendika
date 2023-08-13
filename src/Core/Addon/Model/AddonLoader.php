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

namespace Friendica\Core\Addon\Model;

use Friendica\Core\Addon\Capability\ICanLoadAddons;
use Friendica\Core\Addon\Exception\AddonInvalidConfigFileException;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Util\Strings;

class AddonLoader implements ICanLoadAddons
{
	const STATIC_PATH = 'static';
	/** @var string */
	protected $basePath;
	/** @var IManageConfigValues */
	protected $config;

	public function __construct(string $basePath, IManageConfigValues $config)
	{
		$this->basePath = $basePath;
		$this->config   = $config;
	}

	/** {@inheritDoc} */
	public function getActiveAddonConfig(string $configName): array
	{
		$addons       = array_keys(array_filter($this->config->get('addons') ?? []));
		$returnConfig = [];

		foreach ($addons as $addon) {
			$addonName = Strings::sanitizeFilePathItem(trim($addon));

			$configFile = $this->basePath . '/addon/' . $addonName . '/' . static::STATIC_PATH . '/' . $configName . '.config.php';

			if (!file_exists($configFile)) {
				// Addon unmodified, skipping
				continue;
			}

			$config = include $configFile;

			if (!is_array($config)) {
				throw new AddonInvalidConfigFileException('Error loading config file ' . $configFile);
			}

			$returnConfig = array_merge_recursive($returnConfig, $config);
		}

		return $returnConfig;
	}
}
