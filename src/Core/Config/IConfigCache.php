<?php

namespace Friendica\Core\Config;

/**
 * The interface for a system-wide ConfigCache
 */
interface IConfigCache
{
	/**
	 * @param string $cat     Config category
	 * @param string $key       Config key
	 * @param mixed  $default Default value if it isn't set
	 *
	 * @return mixed Returns the value of the Config entry
	 */
	function get($cat, $key = null, $default = null);

	/**
	 * Sets a value in the config cache. Accepts raw output from the config table
	 *
	 * @param string $cat   Config category
	 * @param string $key   Config key
	 * @param mixed  $value Value to set
	 */
	function set($cat, $key, $value);

	/**
	 * Deletes a value from the config cache
	 *
	 * @param string $cat  Config category
	 * @param string $key  Config key
	 */
	function delete($cat, $key);

	function getAll();
}
