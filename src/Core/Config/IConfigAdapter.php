<?php

namespace Friendica\Core\Config;

/**
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
interface IConfigAdapter
{
	/**
	 * Loads all configuration values into a cached storage.
	 *
	 * @param string  $cat The category of the configuration values to load
	 *
	 * @return void
	 */
	public function load($cat = "config");

	/**
	 * Get a particular user's config variable given the category name
	 * ($family) and a key.
	 *
	 * @param string  $cat           The category of the configuration value
	 * @param string  $k             The configuration key to query
	 * @param mixed   $default_value optional, The value to return if key is not set (default: null)
	 * @param boolean $refresh       optional, If true the config is loaded from the db and not from the cache (default: false)
	 *
	 * @return mixed Stored value or null if it does not exist
	 */
	public function get($cat, $k, $default_value = null, $refresh = false);

	/**
	 * Stores a config value ($value) in the category ($family) under the key ($key)
	 * for the user_id $uid.
	 *
	 * Note: Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $cat   The category of the configuration value
	 * @param string $k     The configuration key to set
	 * @param mixed  $value The value to store
	 *
	 * @return bool Operation success
	 */
	public function set($cat, $k, $value);

	/**
	 * Removes the configured value from the stored cache
	 * and removes it from the database.
	 *
	 * @param string $cat The category of the configuration value
	 * @param string $k   The configuration key to delete
	 *
	 * @return mixed
	 */
	public function delete($cat, $k);
}
