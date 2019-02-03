<?php
/**
 * System Configuration Class
 *
 * @file include/Core/Config.php
 *
 * @brief Contains the class with methods for system configuration
 */
namespace Friendica\Core;

use Friendica\App;
use Friendica\BaseObject;

/**
 * @brief Arbitrary system configuration storage
 *
 * Note:
 * If we ever would decide to return exactly the variable type as entered,
 * we will have fun with the additional features. :-)
 */
class Config extends BaseObject
{
	public static $config = [];

	/**
	 * @var \Friendica\Core\Config\IConfigAdapter
	 */
	private static $adapter = null;

	public static function init()
	{
		// Database isn't ready or populated yet
		if (!self::getApp()->getMode()->has(App\Mode::DBCONFIGAVAILABLE)) {
			return;
		}

		if (self::getConfigValue('system', 'config_adapter') == 'preload') {
			self::$adapter = new Config\PreloadConfigAdapter();
		} else {
			self::$adapter = new Config\JITConfigAdapter();
		}
	}

	/**
	 * @brief Loads all configuration values of family into a cached storage.
	 *
	 * All configuration values of the system are stored in global cache
	 * which is available under the global variable self::$config
	 *
	 * @param string $family The category of the configuration value
	 *
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function load($family = "config")
	{
		// Database isn't ready or populated yet
		if (!self::getApp()->getMode()->has(App\Mode::DBCONFIGAVAILABLE)) {
			return;
		}

		if (empty(self::$adapter)) {
			self::init();
		}

		self::$adapter->load($family);
	}

	/**
	 * @brief Get a particular user's config variable given the category name
	 * ($family) and a key.
	 *
	 * Get a particular config value from the given category ($family)
	 * and the $key from a cached storage in static::config[$uid].
	 * $instore is only used by the set_config function
	 * to determine if the key already exists in the DB
	 * If a key is found in the DB but doesn't exist in
	 * local config cache, pull it into the cache so we don't have
	 * to hit the DB again for this item.
	 *
	 * @param string  $family        The category of the configuration value
	 * @param string  $key           The configuration key to query
	 * @param mixed   $default_value optional, The value to return if key is not set (default: null)
	 * @param boolean $refresh       optional, If true the config is loaded from the db and not from the cache (default: false)
	 *
	 * @return mixed Stored value or null if it does not exist
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function get($family, $key, $default_value = null, $refresh = false)
	{
		// Database isn't ready or populated yet, fallback to file config
		if (!self::getApp()->getMode()->has(App\Mode::DBCONFIGAVAILABLE)) {
			return self::getConfigValue($family, $key, $default_value);
		}

		if (empty(self::$adapter)) {
			self::init();
		}

		return self::$adapter->get($family, $key, $default_value, $refresh);
	}

	/**
	 * @brief Sets a configuration value for system config
	 *
	 * Stores a config value ($value) in the category ($family) under the key ($key)
	 * for the user_id $uid.
	 *
	 * Note: Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $family The category of the configuration value
	 * @param string $key    The configuration key to set
	 * @param mixed  $value  The value to store
	 *
	 * @return bool Operation success
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function set($family, $key, $value)
	{
		// Database isn't ready or populated yet
		if (!self::getApp()->getMode()->has(App\Mode::DBCONFIGAVAILABLE)) {
			return false;
		}

		if (empty(self::$adapter)) {
			self::init();
		}

		return self::$adapter->set($family, $key, $value);
	}

	/**
	 * @brief Deletes the given key from the system configuration.
	 *
	 * Removes the configured value from the stored cache in Config::$config
	 * and removes it from the database.
	 *
	 * @param string $family The category of the configuration value
	 * @param string $key    The configuration key to delete
	 *
	 * @return mixed
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function delete($family, $key)
	{
		// Database isn't ready or populated yet
		if (!self::getApp()->getMode()->has(App\Mode::DBCONFIGAVAILABLE)) {
			return false;
		}

		if (empty(self::$adapter)) {
			self::init();
		}

		return self::$adapter->delete($family, $key);
	}

	/**
	 * Tries to load the specified configuration array into the App->config array.
	 * Doesn't overwrite previously set values by default to prevent default config files to supersede DB Config.
	 *
	 * @param array $config
	 * @param bool  $overwrite Force value overwrite if the config key already exists
	 */
	public static function loadConfigArray(array $config, $overwrite = false)
	{
		foreach ($config as $category => $values) {
			foreach ($values as $key => $value) {
				if ($overwrite) {
					self::setConfigValue($category, $key, $value);
				} else {
					self::setDefaultConfigValue($category, $key, $value);
				}
			}
		}
	}

	/**
	 * @param string $cat     Config category
	 * @param string $k       Config key
	 * @param mixed  $default Default value if it isn't set
	 *
	 * @return string Returns the value of the Config entry
	 */
	public static function getConfigValue($cat, $k = null, $default = null)
	{
		$return = $default;

		if ($cat === 'config') {
			if (isset(self::$config[$k])) {
				$return = self::$config[$k];
			}
		} else {
			if (isset(self::$config[$cat][$k])) {
				$return = self::$config[$cat][$k];
			} elseif ($k == null && isset(self::$config[$cat])) {
				$return = self::$config[$cat];
			}
		}

		return $return;
	}

	/**
	 * Sets a default value in the config cache. Ignores already existing keys.
	 *
	 * @param string $cat Config category
	 * @param string $k   Config key
	 * @param mixed  $v   Default value to set
	 */
	private static function setDefaultConfigValue($cat, $k, $v)
	{
		if (!isset(self::$config[$cat][$k])) {
			self::setConfigValue($cat, $k, $v);
		}
	}

	/**
	 * Sets a value in the config cache. Accepts raw output from the config table
	 *
	 * @param string $cat Config category
	 * @param string $k   Config key
	 * @param mixed  $v   Value to set
	 */
	public static function setConfigValue($cat, $k, $v)
	{
		// Only arrays are serialized in database, so we have to unserialize sparingly
		$value = is_string($v) && preg_match("|^a:[0-9]+:{.*}$|s", $v) ? unserialize($v) : $v;

		if ($cat === 'config') {
			self::$config[$k] = $value;
		} else {
			if (!isset(self::$config[$cat])) {
				self::$config[$cat] = [];
			}

			self::$config[$cat][$k] = $value;
		}
	}

	/**
	 * Deletes a value from the config cache
	 *
	 * @param string $cat Config category
	 * @param string $k   Config key
	 */
	public static function deleteConfigValue($cat, $k)
	{
		if ($cat === 'config') {
			if (isset(self::$config[$k])) {
				unset(self::$config[$k]);
			}
		} else {
			if (isset(self::$config[$cat][$k])) {
				unset(self::$config[$cat][$k]);
			}
		}
	}

	/**
	 * Returns the whole configuration
	 *
	 * @return array The configuration
	 */
	public static function getAll()
	{
		return self::$config;
	}
}
