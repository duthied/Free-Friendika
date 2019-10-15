<?php

namespace Friendica\Core\Config\Cache;

use ParagonIE\HiddenString\HiddenString;

/**
 * The Friendica config cache for the application
 * Initial, all *.config.php files are loaded into this cache with the
 * ConfigFileLoader ( @see ConfigFileLoader )
 */
class ConfigCache
{
	/**
	 * @var array
	 */
	private $config;

	/**
	 * @var bool
	 */
	private $hidePasswordOutput;

	/**
	 * @param array $config             A initial config array
	 * @param bool  $hidePasswordOutput True, if cache variables should take extra care of password values
	 */
	public function __construct(array $config = [], bool $hidePasswordOutput = true)
	{
		$this->hidePasswordOutput = $hidePasswordOutput;
		$this->load($config);
	}

	/**
	 * Tries to load the specified configuration array into the config array.
	 * Doesn't overwrite previously set values by default to prevent default config files to supersede DB Config.
	 *
	 * @param array $config
	 * @param bool  $overwrite Force value overwrite if the config key already exists
	 */
	public function load(array $config, bool $overwrite = false)
	{
		$categories = array_keys($config);

		foreach ($categories as $category) {
			if (is_array($config[$category])) {
				$keys = array_keys($config[$category]);

				foreach ($keys as $key) {
					$value = $config[$category][$key];
					if (isset($value)) {
						if ($overwrite) {
							$this->set($category, $key, $value);
						} else {
							$this->setDefault($category, $key, $value);
						}
					}
				}
			}
		}
	}

	/**
	 * Gets a value from the config cache.
	 *
	 * @param string $cat Config category
	 * @param string $key Config key
	 *
	 * @return null|mixed Returns the value of the Config entry or null if not set
	 */
	public function get(string $cat, string $key = null)
	{
		if (isset($this->config[$cat][$key])) {
			return $this->config[$cat][$key];
		} elseif (!isset($key) && isset($this->config[$cat])) {
			return $this->config[$cat];
		} else {
			return null;
		}
	}

	/**
	 * Sets a default value in the config cache. Ignores already existing keys.
	 *
	 * @param string $cat   Config category
	 * @param string $key   Config key
	 * @param mixed  $value Default value to set
	 */
	private function setDefault(string $cat, string $key, $value)
	{
		if (!isset($this->config[$cat][$key])) {
			$this->set($cat, $key, $value);
		}
	}

	/**
	 * Sets a value in the config cache. Accepts raw output from the config table
	 *
	 * @param string $cat   Config category
	 * @param string $key   Config key
	 * @param mixed  $value Value to set
	 *
	 * @return bool True, if the value is set
	 */
	public function set(string $cat, string $key, $value)
	{
		if (!isset($this->config[$cat])) {
			$this->config[$cat] = [];
		}

		if ($this->hidePasswordOutput &&
		    $key == 'password' &&
		    is_string($value)) {
			$this->config[$cat][$key] = new HiddenString((string)$value);
		} else {
			$this->config[$cat][$key] = $value;
		}
		return true;
	}

	/**
	 * Deletes a value from the config cache.
	 *
	 * @param string $cat Config category
	 * @param string $key Config key
	 *
	 * @return bool true, if deleted
	 */
	public function delete(string $cat, string $key)
	{
		if (isset($this->config[$cat][$key])) {
			unset($this->config[$cat][$key]);
			if (count($this->config[$cat]) == 0) {
				unset($this->config[$cat]);
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns the whole configuration
	 *
	 * @return array The configuration
	 */
	public function getAll()
	{
		return $this->config;
	}

	/**
	 * Returns an array with missing categories/Keys
	 *
	 * @param array $config The array to check
	 *
	 * @return array
	 */
	public function keyDiff(array $config)
	{
		$return = [];

		$categories = array_keys($config);

		foreach ($categories as $category) {
			if (is_array($config[$category])) {
				$keys = array_keys($config[$category]);

				foreach ($keys as $key) {
					if (!isset($this->config[$category][$key])) {
						$return[$category][$key] = $config[$category][$key];
					}
				}
			}
		}

		return $return;
	}
}
