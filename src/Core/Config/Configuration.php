<?php

namespace Friendica\Core\Config;

use Friendica\Model;

/**
 * This class is responsible for all system-wide configuration values in Friendica
 * There are two types of storage
 * - The Config-Files    (loaded into the FileCache @see Cache\ConfigCache )
 * - The Config-DB-Table (per Config-DB-model @see Model\Config\Config )
 */
abstract class Configuration
{
	/**
	 * @var Cache\ConfigCache
	 */
	protected $configCache;

	/**
	 * @var Model\Config\Config
	 */
	protected $configModel;

	/**
	 * @param Cache\ConfigCache  $configCache The configuration cache (based on the config-files)
	 * @param Model\Config\Config $configModel The configuration model
	 */
	public function __construct(Cache\ConfigCache $configCache, Model\Config\Config $configModel)
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

	abstract public function load(string $cat = 'config');
	abstract public function get(string $cat, string $key, $default_value = null, bool $refresh = false);
	abstract public function set(string $cat, string $key, $value);
	abstract public function delete(string $cat, string $key);
}
