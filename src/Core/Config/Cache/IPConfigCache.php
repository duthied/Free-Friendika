<?php

namespace Friendica\Core\Config\Cache;

/**
 * The interface for a user-specific config cache
 */
interface IPConfigCache
{
	/**
	 * Tries to load the specified configuration array into the user specific config array.
	 * Doesn't overwrite previously set values by default to prevent default config files to supersede DB Config.
	 *
	 * @param int   $uid
	 * @param array $config
	 */
	function loadP($uid, array $config);

	/**
	 * Retrieves a value from the user config cache
	 *
	 * @param int    $uid     User Id
	 * @param string $cat     Config category
	 * @param string $key     Config key
	 *
	 * @return null|string The value of the config entry or null if not set
	 */
	function getP($uid, $cat, $key = null);

	/**
	 * Sets a value in the user config cache
	 *
	 * Accepts raw output from the pconfig table
	 *
	 * @param int    $uid   User Id
	 * @param string $cat   Config category
	 * @param string $key   Config key
	 * @param mixed  $value Value to set
	 */
	function setP($uid, $cat, $key, $value);

	/**
	 * Deletes a value from the user config cache
	 *
	 * @param int    $uid User Id
	 * @param string $cat Config category
	 * @param string $key Config key
	 *
	 * @return bool true, if deleted
	 */
	function deleteP($uid, $cat, $key);

	/**
	 * Returns the whole configuration cache
	 *
	 * @return array
	 */
	function getAll();
}
