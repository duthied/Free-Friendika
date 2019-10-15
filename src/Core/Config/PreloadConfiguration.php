<?php

namespace Friendica\Core\Config;

use Friendica\Model;

/**
 * This class implements the preload configuration, which will cache
 * all config values per call in a cache.
 *
 * Minimizes the number of database queries to retrieve configuration values at the cost of memory.
 */
class PreloadConfiguration extends Configuration
{
	/** @var bool */
	private $config_loaded;

	/**
	 * @param Cache\ConfigCache   $configCache The configuration cache (based on the config-files)
	 * @param Model\Config\Config $configModel The configuration model
	 */
	public function __construct(Cache\ConfigCache $configCache, Model\Config\Config $configModel)
	{
		parent::__construct($configCache, $configModel);
		$this->config_loaded = false;

		$this->load();
	}

	/**
	 * {@inheritDoc}
	 *
	 * This loads all config values everytime load is called
	 *
	 */
	public function load(string $cat = 'config')
	{
		// Don't load the whole configuration twice
		if ($this->config_loaded) {
			return;
		}

		// If not connected, do nothing
		if (!$this->configModel->isConnected()) {
			return;
		}

		$config              = $this->configModel->load();
		$this->config_loaded = true;

		// load the whole category out of the DB into the cache
		$this->configCache->load($config, true);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get(string $cat, string $key, $default_value = null, bool $refresh = false)
	{
		if ($refresh) {
			if ($this->configModel->isConnected()) {
				$config = $this->configModel->get($cat, $key);
				if (isset($config)) {
					$this->configCache->set($cat, $key, $config);
				}
			}
		}

		// use the config cache for return
		$result = $this->configCache->get($cat, $key);

		return (isset($result)) ? $result : $default_value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function set(string $cat, string $key, $value)
	{
		if (!$this->config_loaded) {
			$this->load();
		}

		// set the cache first
		$cached = $this->configCache->set($cat, $key, $value);

		// If there is no connected adapter, we're finished
		if (!$this->configModel->isConnected()) {
			return $cached;
		}

		$stored = $this->configModel->set($cat, $key, $value);

		return $cached && $stored;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete(string $cat, string $key)
	{
		if ($this->config_loaded) {
			$this->load();
		}

		$cacheRemoved = $this->configCache->delete($cat, $key);

		if (!$this->configModel->isConnected()) {
			return $cacheRemoved;
		}

		$storeRemoved = $this->configModel->delete($cat, $key);

		return $cacheRemoved || $storeRemoved;
	}
}
