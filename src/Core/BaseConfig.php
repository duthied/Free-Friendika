<?php

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
