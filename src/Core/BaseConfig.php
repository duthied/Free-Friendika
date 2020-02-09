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

namespace Friendica\Core;

use Friendica\Core\Config\Cache;
use Friendica\Core\Config\IConfig;
use Friendica\Model;

/**
 * This class is responsible for all system-wide configuration values in Friendica
 * There are two types of storage
 * - The Config-Files    (loaded into the FileCache @see Cache\ConfigCache)
 * - The Config-DB-Table (per Config-DB-model @see Model\Config\Config)
 */
abstract class BaseConfig implements IConfig
{
	/**
	 * @var Cache
	 */
	protected $configCache;

	/**
	 * @var Model\Config\Config
	 */
	protected $configModel;

	/**
	 * @param Cache $configCache The configuration cache (based on the config-files)
	 * @param Model\Config\Config          $configModel The configuration model
	 */
	public function __construct(Cache $configCache, Model\Config\Config $configModel)
	{
		$this->configCache = $configCache;
		$this->configModel = $configModel;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getCache()
	{
		return $this->configCache;
	}
}
