<?php

namespace Friendica\Core\Config;

/**
 * This class is responsible for the user-specific configuration values in Friendica
 * The values are set through the Config-DB-Table (per Config-DB-adapter @see Adapter\IPConfigAdapter )
 *
 * The configuration cache (@see Cache\IPConfigCache ) is used for temporary caching of database calls. This will
 * increase the performance.
 */
class PConfiguration
{
	/**
	 * @var Cache\IPConfigCache
	 */
	private $configCache;

	/**
	 * @var Adapter\IPConfigAdapter
	 */
	private $configAdapter;

	/**
	 * @param Cache\IPConfigCache     $configCache   The configuration cache
	 * @param Adapter\IPConfigAdapter $configAdapter The configuration DB-backend
	 */
	public function __construct(Cache\IPConfigCache $configCache, Adapter\IPConfigAdapter $configAdapter)
	{
		$this->configCache = $configCache;
		$this->configAdapter = $configAdapter;
	}

	/**
	 * @brief Loads all configuration values of a user's config family into a cached storage.
	 *
	 * All configuration values of the given user are stored with the $uid in
	 * the cache ( @see IPConfigCache )
	 *
	 * @param string $uid The user_id
	 * @param string $cat The category of the configuration value
	 *
	 * @return void
	 */
	public function load($uid, $cat = 'config')
	{
		// If not connected, do nothing
		if (!$this->configAdapter->isConnected()) {
			return;
		}

		// load the whole category out of the DB into the cache
		$this->configCache->loadP($uid, $this->configAdapter->load($uid, $cat));
	}

	/**
	 * @brief Get a particular user's config variable given the category name
	 * ($cat) and a key.
	 *
	 * Get a particular user's config value from the given category ($cat)
	 * and the $key with the $uid from a cached storage either from the $this->configAdapter
	 * (@see IConfigAdapter ) or from the $this->configCache (@see IConfigCache ).
	 *
	 * @param string  $uid           The user_id
	 * @param string  $cat           The category of the configuration value
	 * @param string  $key           The configuration key to query
	 * @param mixed   $default_value optional, The value to return if key is not set (default: null)
	 * @param boolean $refresh       optional, If true the config is loaded from the db and not from the cache (default: false)
	 *
	 * @return mixed Stored value or null if it does not exist
	 */
	public function get($uid, $cat, $key, $default_value = null, $refresh = false)
	{
		// Return the value of the cache if found and no refresh is forced
		if (!$refresh && $this->configCache->hasP($uid, $cat, $key)) {
			return $this->configCache->getP($uid, $cat, $key);
		}

		// if we don't find the value in the cache and the adapter isn't ready, return the default value
		if (!$this->configAdapter->isConnected()) {
			return $default_value;
		}

		// load DB value to cache
		$dbvalue = $this->configAdapter->get($uid, $cat, $key);

		if ($dbvalue !== '!<unset>!') {
			$this->configCache->setP($uid, $cat, $key, $dbvalue);
			return $dbvalue;
		} else {
			return $default_value;
		}
	}

	/**
	 * @brief Sets a configuration value for a user
	 *
	 * Stores a config value ($value) in the category ($family) under the key ($key)
	 * for the user_id $uid.
	 *
	 * @note  Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $uid    The user_id
	 * @param string $cat    The category of the configuration value
	 * @param string $key    The configuration key to set
	 * @param mixed  $value  The value to store
	 *
	 * @return bool Operation success
	 */
	public function set($uid, $cat, $key, $value)
	{
		// set the cache first
		$cached = $this->configCache->setP($uid, $cat, $key, $value);

		// If there is no connected adapter, we're finished
		if (!$this->configAdapter->isConnected()) {
			return $cached;
		}

		$stored = $this->configAdapter->set($uid, $cat, $key, $value);

		return $cached && $stored;
	}

	/**
	 * @brief Deletes the given key from the users's configuration.
	 *
	 * Removes the configured value from the stored cache in $this->configCache
	 * (@see ConfigCache ) and removes it from the database (@see IConfigAdapter )
	 * with the given $uid.
	 *
	 * @param string $uid The user_id
	 * @param string $cat The category of the configuration value
	 * @param string $key The configuration key to delete
	 *
	 * @return bool
	 */
	public function delete($uid, $cat, $key)
	{
		$cacheRemoved = $this->configCache->deleteP($uid, $cat, $key);

		if (!$this->configAdapter->isConnected()) {
			return $cacheRemoved;
		}

		$storeRemoved = $this->configAdapter->delete($uid, $cat, $key);

		return $cacheRemoved || $storeRemoved;
	}
}
