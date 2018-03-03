<?php

namespace Friendica\Core\Config;

use dba;
use Exception;
use Friendica\BaseObject;

require_once 'include/dba.php';

/**
 * Preload PConfigAdapter
 *
 * Minimize the number of database queries to retrieve configuration values at the cost of memory.
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class PreloadPConfigAdapter extends BaseObject implements IPConfigAdapter
{
	private $config_loaded = false;

	public function __construct($uid)
	{
		$this->load($uid, 'config');
	}

	public function load($uid, $family)
	{
		if ($this->config_loaded) {
			return;
		}

		$a = self::getApp();

		$pconfigs = dba::select('pconfig', ['cat', 'v', 'k'], ['uid' => $uid]);
		while ($pconfig = dba::fetch($pconfigs)) {
			$cat   = $pconfig['cat'];
			$k     = $pconfig['k'];
			$value = (preg_match("|^a:[0-9]+:{.*}$|s", $pconfig['v']) ? unserialize($pconfig['v']) : $pconfig['v']);

			$a->config[$uid][$cat][$k] = $value;
		}
		dba::close($pconfigs);

		$this->config_loaded = true;
	}

	public function get($uid, $cat, $k, $default_value = null, $refresh = false)
	{
		$a = self::getApp();

		$return = $default_value;

		if (isset($a->config[$uid][$cat][$k])) {
			$return = $a->config[$uid][$cat][$k];
		}

		return $return;
	}

	public function set($uid, $cat, $k, $value)
	{
		$a = self::getApp();

		// We store our setting values as strings.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$compare_value = !is_array($value) ? (string)$value : $value;

		if ($this->get($uid, $cat, $k) === $compare_value) {
			return true;
		}

		$a->config[$uid][$cat][$k] = $value;

		// manage array value
		$dbvalue = is_array($value) ? serialize($value) : $value;

		$result = dba::update('pconfig', ['v' => $dbvalue], ['uid' => $uid, 'cat' => $cat, 'k' => $k], true);

		if (!$result) {
			throw new Exception('Unable to store config value in [' . $uid . '][' . $cat . '][' . $k . ']');
		}

		return true;
	}

	public function delete($uid, $cat, $k)
	{
		$a = self::getApp();

		if (!isset($a->config[$uid][$cat][$k])) {
			return true;
		}

		unset($a->config[$uid][$cat][$k]);

		$result = dba::delete('pconfig', ['uid' => $uid, 'cat' => $cat, 'k' => $k]);

		return $result;
	}
}
