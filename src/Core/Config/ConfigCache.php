<?php

namespace Friendica\Core\Config;

class ConfigCache implements IConfigCache, IPConfigCache
{
	/**
	 * NEVER, EVER use this public config array outside of the class
	 * It is only public due to backward compatibility to .htconfig.php
	 *
	 * @var array The cached config array
	 */
	public $config;

	public function __construct($config = [], $overwrite = false)
	{
		$this->config = [];

		if (isset($config)) {
			$this->loadConfigArray($config, $overwrite);
		}
	}

	/**
	 * Tries to load the specified configuration array into the App->config array.
	 * Doesn't overwrite previously set values by default to prevent default config files to supersede DB Config.
	 *
	 * @param array $config
	 * @param bool  $overwrite Force value overwrite if the config key already exists
	 */
	public function loadConfigArray(array $config, $overwrite = false)
	{
		foreach ($config as $category => $values) {
			foreach ($values as $key => $value) {
				if ($overwrite) {
					self::set($category, $key, $value);
				} else {
					self::setDefault($category, $key, $value);
				}
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($cat, $key = null, $default = null)
	{
		$return = $default;

		if ($cat === 'config') {
			if (isset($this->config[$key])) {
				$return = $this->config[$key];
			}
		} else {
			if (isset($this->config[$cat][$key])) {
				$return = $this->config[$cat][$key];
			} elseif ($key == null && isset($this->config[$cat])) {
				$return = $this->config[$cat];
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
	private function setDefault($cat, $k, $v)
	{
		if (!isset($this->config[$cat][$k])) {
			self::set($cat, $k, $v);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function set($cat, $key, $value)
	{
		// Only arrays are serialized in database, so we have to unserialize sparingly
		$value = is_string($value) && preg_match("|^a:[0-9]+:{.*}$|s", $value) ? unserialize($value) : $value;

		if ($cat === 'config') {
			$this->config[$key] = $value;
		} else {
			if (!isset($this->config[$cat])) {
				$this->config[$cat] = [];
			}

			$this->config[$cat][$key] = $value;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete($cat, $key)
	{
		if ($cat === 'config') {
			if (isset($this->config[$key])) {
				unset($this->config[$key]);
			}
		} else {
			if (isset($this->config[$cat][$key])) {
				unset($this->config[$cat][$key]);
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getP($uid, $cat, $key = null, $default = null)
	{
		$return = $default;

		if (isset($this->config[$uid][$cat][$key])) {
			$return = $this->config[$uid][$cat][$key];
		} elseif ($key == null && isset($this->config[$uid][$cat])) {
			$return = $this->config[$uid][$cat];
		}

		return $return;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setP($uid, $cat, $key, $value)
	{
		// Only arrays are serialized in database, so we have to unserialize sparingly
		$value = is_string($value) && preg_match("|^a:[0-9]+:{.*}$|s", $value) ? unserialize($value) : $value;

		if (!isset($this->config[$uid]) || !is_array($this->config[$uid])) {
			$this->config[$uid] = [];
		}

		if (!isset($this->config[$uid][$cat]) || !is_array($this->config[$uid][$cat])) {
			$this->config[$uid][$cat] = [];
		}

		if ($key === null) {
			$this->config[$uid][$cat] = $value;
		} else {
			$this->config[$uid][$cat][$key] = $value;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function deleteP($uid, $cat, $key)
	{
		if (isset($this->config[$uid][$cat][$key])) {
			unset($this->config[$uid][$cat][$key]);
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
}
