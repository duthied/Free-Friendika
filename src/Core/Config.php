<?php
/**
 * System Configuration Class
 *
 * @file include/Core/Config.php
 *
 * Contains the class with methods for system configuration
 */
namespace Friendica\Core;

use Friendica\DI;

/**
 * Arbitrary system configuration storage
 *
 * Note:
 * If we ever would decide to return exactly the variable type as entered,
 * we will have fun with the additional features. :-)
 */
class Config
{
	/**
	 * Stores a config value ($value) in the category ($cat) under the key ($key)
	 *
	 * Note: Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $cat The category of the configuration value
	 * @param string $key    The configuration key to set
	 * @param mixed  $value  The value to store
	 *
	 * @return bool Operation success
	 */
	public static function set($cat, $key, $value)
	{
		return DI::config()->set($cat, $key, $value);
	}

	/**
	 * Deletes the given key from the system configuration.
	 *
	 * @param string $cat The category of the configuration value
	 * @param string $key    The configuration key to delete
	 *
	 * @return bool
	 */
	public static function delete($cat, $key)
	{
		return DI::config()->delete($cat, $key);
	}
}
