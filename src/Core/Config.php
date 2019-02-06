<?php
/**
 * System Configuration Class
 *
 * @file include/Core/Config.php
 *
 * @brief Contains the class with methods for system configuration
 */
namespace Friendica\Core;

use Friendica\Core\Config\ConfigCache;
use Friendica\Core\Config\IConfigAdapter;
use Friendica\Core\Config\IConfigCache;

/**
 * @brief Arbitrary system configuration storage
 *
 * Note:
 * If we ever would decide to return exactly the variable type as entered,
 * we will have fun with the additional features. :-)
 */
class Config
{
	/**
	 * @var Config\IConfigAdapter
	 */
	private static $adapter;

	/**
	 * @var Config\IConfigCache
	 */
	private static $cache;

	/**
	 * Initialize the config with only the cache
	 *
	 * @param Config\IConfigCache $cache  The configuration cache
	 */
	public static function init(Config\IConfigCache $cache)
	{
		self::$cache  = $cache;
	}

	/**
	 * Add the adapter for DB-backend
	 *
	 * @param Config\IConfigAdapter $adapter
	 */
	public static function setAdapter(Config\IConfigAdapter $adapter)
	{
		self::$adapter = $adapter;
	}

	/**
	 * @brief Loads all configuration values of family into a cached storage.
	 *
	 * All configuration values of the system are stored in the cache ( @see IConfigCache )
	 *
	 * @param string $family The category of the configuration value
	 *
	 * @return void
	 */
	public static function load($family = "config")
	{
		if (!isset(self::$adapter)) {
			return;
		}

		self::$adapter->load($family);
	}

	/**
	 * @brief Get a particular user's config variable given the category name
	 * ($family) and a key.
	 *
	 * Get a particular config value from the given category ($family)
	 * and the $key from a cached storage either from the self::$adapter
	 * (@see IConfigAdapter ) or from the static::$cache (@see IConfigCache ).
	 *
	 * @param string  $family        The category of the configuration value
	 * @param string  $key           The configuration key to query
	 * @param mixed   $default_value optional, The value to return if key is not set (default: null)
	 * @param boolean $refresh       optional, If true the config is loaded from the db and not from the cache (default: false)
	 *
	 * @return mixed Stored value or null if it does not exist
	 */
	public static function get($family, $key, $default_value = null, $refresh = false)
	{
		if (!isset(self::$adapter)) {
			return self::$cache->get($family, $key, $default_value);
		}

		return self::$adapter->get($family, $key, $default_value, $refresh);
	}

	/**
	 * @brief Sets a configuration value for system config
	 *
	 * Stores a config value ($value) in the category ($family) under the key ($key)
	 *
	 * Note: Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $family The category of the configuration value
	 * @param string $key    The configuration key to set
	 * @param mixed  $value  The value to store
	 *
	 * @return bool Operation success
	 */
	public static function set($family, $key, $value)
	{
		if (!isset(self::$adapter)) {
			self::$cache->set($family, $key, $value);
			return true;
		}

		return self::$adapter->set($family, $key, $value);
	}

	/**
	 * @brief Deletes the given key from the system configuration.
	 *
	 * Removes the configured value from the stored cache in self::$config
	 * (@see ConfigCache ) and removes it from the database (@see IConfigAdapter ).
	 *
	 * @param string $family The category of the configuration value
	 * @param string $key    The configuration key to delete
	 *
	 * @return mixed
	 */
	public static function delete($family, $key)
	{
		if (!isset(self::$adapter)) {
			self::$cache->delete($family, $key);
		}

		return self::$adapter->delete($family, $key);
	}
}
