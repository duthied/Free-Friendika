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
