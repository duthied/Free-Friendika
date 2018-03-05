<?php

namespace Friendica\Core\Config;

use dba;
use Exception;
use Friendica\App;
use Friendica\BaseObject;
use Friendica\Database\DBM;

require_once 'include/dba.php';

/**
 * Preload ConfigAdapter
 *
 * Minimize the number of database queries to retrieve configuration values at the cost of memory.
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class PreloadConfigAdapter extends BaseObject implements IConfigAdapter
{
	private $config_loaded = false;

	public function __construct()
	{
		$this->load();
	}

	public function load($family = 'config')
	{
		if ($this->config_loaded) {
			return;
		}

		$configs = dba::select('config', ['cat', 'v', 'k']);
		while ($config = dba::fetch($configs)) {
			$this->setPreloadedValue($config['cat'], $config['k'], $config['v']);
		}
		dba::close($configs);

		$this->config_loaded = true;
	}

	public function get($cat, $k, $default_value = null, $refresh = false)
	{
		if ($refresh) {
			$config = dba::selectFirst('config', ['v'], ['cat' => $cat, 'k' => $k]);
			if (DBM::is_result($config)) {
				$this->setPreloadedValue($cat, $k, $config['v']);
			}
		}

		$return = $this->getPreloadedValue($cat, $k, $default_value);

		return $return;
	}

	public function set($cat, $k, $value)
	{
		// We store our setting values as strings.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$compare_value = !is_array($value) ? (string)$value : $value;

		if ($this->getPreloadedValue($cat, $k) === $compare_value) {
			return true;
		}

		$this->setPreloadedValue($cat, $k, $value);

		// manage array value
		$dbvalue = is_array($value) ? serialize($value) : $value;

		$result = dba::update('config', ['v' => $dbvalue], ['cat' => $cat, 'k' => $k], true);
		if (!$result) {
			throw new Exception('Unable to store config value in [' . $cat . '][' . $k . ']');
		}

		return true;
	}

	public function delete($cat, $k)
	{
		$this->deletePreloadedValue($cat, $k);

		$result = dba::delete('config', ['cat' => $cat, 'k' => $k]);

		return $result;
	}

	/**
	 * Retrieves a preloaded value from the App config cache
	 *
	 * @param string $cat
	 * @param string $k
	 * @param mixed  $default
	 */
	private function getPreloadedValue($cat, $k, $default = null)
	{
		$a = self::getApp();

		$return = $default;

		if ($cat === 'config') {
			if (isset($a->config[$k])) {
				$return = $a->config[$k];
			}
		} else {
			if (isset($a->config[$cat][$k])) {
				$return = $a->config[$cat][$k];
			}
		}

		return $return;
	}

	/**
	 * Sets a preloaded value in the App config cache
	 *
	 * Accepts raw output from the config table
	 *
	 * @param string $cat
	 * @param string $k
	 * @param mixed $v
	 */
	private function setPreloadedValue($cat, $k, $v)
	{
		$a = self::getApp();

		// Only arrays are serialized in database, so we have to unserialize sparingly
		$value = is_string($v) && preg_match("|^a:[0-9]+:{.*}$|s", $v) ? unserialize($v) : $v;

		if ($cat === 'config') {
			$a->config[$k] = $value;
		} else {
			$a->config[$cat][$k] = $value;
		}
	}

	/**
	 * Deletes a preloaded value from the App config cache
	 *
	 * @param string $cat
	 * @param string $k
	 */
	private function deletePreloadedValue($cat, $k)
	{
		$a = self::getApp();

		if ($cat === 'config') {
			if (isset($a->config[$k])) {
				unset($a->config[$k]);
			}
		} else {
			if (isset($a->config[$cat][$k])) {
				unset($a->config[$cat][$k]);
			}
		}
	}
}
