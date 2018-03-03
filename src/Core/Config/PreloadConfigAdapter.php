<?php

namespace Friendica\Core\Config;

use dba;
use Exception;
use Friendica\BaseObject;

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

		$a = self::getApp();

		$configs = dba::select('config', ['cat', 'v', 'k']);
		while ($config = dba::fetch($configs)) {
			$cat   = $config['cat'];
			$k     = $config['k'];
			$value = (preg_match("|^a:[0-9]+:{.*}$|s", $config['v']) ? unserialize($config['v']) : $config['v']);

			if ($cat === 'config') {
				$a->config[$k] = $value;
			} else {
				$a->config[$cat][$k] = $value;
			}
		}
		dba::close($configs);

		$this->config_loaded = true;
	}

	public function get($cat, $k, $default_value = null, $refresh = false)
	{
		$a = self::getApp();

		$return = $default_value;

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

	public function set($cat, $k, $value)
	{
		$a = self::getApp();

		// We store our setting values as strings.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$compare_value = !is_array($value) ? (string)$value : $value;

		if ($this->get($cat, $k) === $compare_value) {
			return true;
		}

		if ($cat === 'config') {
			$a->config[$k] = $value;
		} else {
			$a->config[$cat][$k] = $value;
		}

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

		$result = dba::delete('config', ['cat' => $cat, 'k' => $k]);

		return $result;
	}
}
