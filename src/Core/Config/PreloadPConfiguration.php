<?php

namespace Friendica\Core\Config;

use Friendica\Model;

/**
 * This class implements the preload configuration, which will cache
 * all user config values per call in a cache.
 *
 * Minimizes the number of database queries to retrieve configuration values at the cost of memory.
 */
class PreloadPConfiguration extends PConfiguration
{
	/** @var array */
	private $config_loaded;

	/**
	 * @param Cache\PConfigCache   $configCache The configuration cache
	 * @param Model\Config\PConfig $configModel The configuration model
	 */
	public function __construct(Cache\PConfigCache $configCache, Model\Config\PConfig $configModel)
	{
		parent::__construct($configCache, $configModel);
		$this->config_loaded = [];
	}

	/**
	 * {@inheritDoc}
	 *
	 * This loads all config values everytime load is called
	 *
	 */
	public function load($uid, string $cat = 'config')
	{
		// Don't load the whole configuration twice or with invalid uid
		if (!is_int($uid) || !empty($this->config_loaded[$uid])) {
			return;
		}

		// If not connected, do nothing
		if (!$this->configModel->isConnected()) {
			return;
		}

		$config                    = $this->configModel->load($uid);
		$this->config_loaded[$uid] = true;

		// load the whole category out of the DB into the cache
		$this->configCache->load($uid, $config);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get($uid, string $cat, string $key, $default_value = null, bool $refresh = false)
	{
		if (!is_int($uid)) {
			return $default_value;
		}

		if (empty($this->config_loaded[$uid])) {
			$this->load($uid);
		} elseif ($refresh) {
			if ($this->configModel->isConnected()) {
				$config = $this->configModel->get($uid, $cat, $key);
				if (isset($config)) {
					$this->configCache->set($uid, $cat, $key, $config);
				}
			}
		}

		// use the config cache for return
		$result = $this->configCache->get($uid, $cat, $key);

		return (isset($result)) ? $result : $default_value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function set($uid, string $cat, string $key, $value)
	{
		if (!is_int($uid)) {
			return false;
		}

		if (empty($this->config_loaded[$uid])) {
			$this->load($uid);
		}

		// set the cache first
		$cached = $this->configCache->set($uid, $cat, $key, $value);

		// If there is no connected adapter, we're finished
		if (!$this->configModel->isConnected()) {
			return $cached;
		}

		$stored = $this->configModel->set($uid, $cat, $key, $value);

		return $cached && $stored;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete($uid, string $cat, string $key)
	{
		if (!is_int($uid)) {
			return false;
		}

		if (empty($this->config_loaded[$uid])) {
			$this->load($uid);
		}

		$cacheRemoved = $this->configCache->delete($uid, $cat, $key);

		if (!$this->configModel->isConnected()) {
			return $cacheRemoved;
		}

		$storeRemoved = $this->configModel->delete($uid, $cat, $key);

		return $cacheRemoved || $storeRemoved;
	}
}
