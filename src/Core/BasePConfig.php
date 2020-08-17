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

use Friendica\Core\PConfig\Cache;
use Friendica\Core\PConfig\IPConfig;
use Friendica\Model;

/**
 * This class is responsible for the user-specific configuration values in Friendica
 * The values are set through the Config-DB-Table (per Config-DB-model @see Model\Config\PConfig)
 *
 * The configuration cache (@see Cache\PConfigCache) is used for temporary caching of database calls. This will
 * increase the performance.
 */
abstract class BasePConfig implements IPConfig
{
	/**
	 * @var Cache
	 */
	protected $configCache;

	/**
	 * @var Model\Config\PConfig
	 */
	protected $configModel;

	/**
	 * @param Cache $configCache The configuration cache
	 * @param Model\Config\PConfig          $configModel The configuration model
	 */
	public function __construct(Cache $configCache, Model\Config\PConfig $configModel)
	{
		$this->configCache = $configCache;
		$this->configModel = $configModel;
	}

	/**
	 * Returns the Config Cache
	 *
	 * @return Cache
	 */
	public function getCache()
	{
		return $this->configCache;
	}
}
