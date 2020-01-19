<?php

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
