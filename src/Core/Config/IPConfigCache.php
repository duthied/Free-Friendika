<?php

namespace Friendica\Core\Config;

/**
 * The interface for a user-specific config cache
 */
interface IPConfigCache
{
	/**
	 * Retrieves a value from the user config cache
	 *
	 * @param int    $uid     User Id
	 * @param string $cat     Config category
	 * @param string $key     Config key
	 * @param mixed  $default Default value if key isn't set
	 *
	 * @return string The value of the config entry
	 */
	function getP($uid, $cat, $key = null, $default = null);

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
	 */
	function deleteP($uid, $cat, $key);

	function getAll();
}
