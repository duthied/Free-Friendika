<?php

namespace Friendica\Core\Config\Adapter;

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
	 * @return array
	 */
	public function load($cat = "config");

	/**
	 * Get a particular user's config variable given the category name
	 * ($family) and a key.
	 *
	 * @param string  $cat The category of the configuration value
	 * @param string  $key The configuration key to query
	 *
	 * @return mixed Stored value or "!<unset>!" if it does not exist
	 */
	public function get($cat, $key);

	/**
	 * Stores a config value ($value) in the category ($family) under the key ($key).
	 *
	 * Note: Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $cat   The category of the configuration value
	 * @param string $key   The configuration key to set
	 * @param mixed  $value The value to store
	 *
	 * @return bool Operation success
	 */
	public function set($cat, $key, $value);

	/**
	 * Removes the configured value from the stored cache
	 * and removes it from the database.
	 *
	 * @param string $cat The category of the configuration value
	 * @param string $key   The configuration key to delete
	 *
	 * @return mixed
	 */
	public function delete($cat, $key);

	/**
	 * Checks, if the current adapter is connected to the backend
	 *
	 * @return bool
	 */
	public function isConnected();

	/**
	 * Checks, if a config value ($value) in the category ($cat) is already loaded.
	 *
	 * @param string $cat The configuration category
	 * @param string $key The configuration key
	 *
	 * @return bool
	 */
	public function isLoaded($cat, $key);
}
