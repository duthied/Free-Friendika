<?php

namespace Friendica\Core\Config;

use dba;
use Exception;
use Friendica\App;
use Friendica\BaseObject;
use Friendica\Database\DBM;

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

		$pconfigs = dba::select('pconfig', ['cat', 'v', 'k'], ['uid' => $uid]);
		while ($pconfig = dba::fetch($pconfigs)) {
			$this->setPreloadedValue($uid, $pconfig['cat'], $pconfig['k'], $pconfig['v']);
		}
		dba::close($pconfigs);

		$this->config_loaded = true;
	}

	public function get($uid, $cat, $k, $default_value = null, $refresh = false)
	{
		if ($refresh) {
			$config = dba::selectFirst('pconfig', ['v'], ['uid' => $uid, 'cat' => $cat, 'k' => $k]);
			if (DBM::is_result($config)) {
				$this->setPreloadedValue($uid, $cat, $k, $config['v']);
			} else {
				$this->deletePreloadedValue($uid, $cat, $k);
			}
		}

		$return = $this->getPreloadedValue($uid, $cat, $k, $default_value);

		return $return;
	}

	public function set($uid, $cat, $k, $value)
	{
		// We store our setting values as strings.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$compare_value = !is_array($value) ? (string)$value : $value;

		if ($this->getPreloadedValue($uid, $cat, $k) === $compare_value) {
			return true;
		}

		$this->setPreloadedValue($uid, $cat, $k, $value);

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
		$this->deletePreloadedValue($uid, $cat, $k);

		$result = dba::delete('pconfig', ['uid' => $uid, 'cat' => $cat, 'k' => $k]);

		return $result;
	}


	/**
	 * Retrieves a preloaded value from the App user config cache
	 *
	 * @param int    $uid
	 * @param string $cat
	 * @param string $k
	 * @param mixed  $default
	 */
	private function getPreloadedValue($uid, $cat, $k, $default = null)
	{
		$a = self::getApp();

		$return = $default;

		if (isset($a->config[$uid][$cat][$k])) {
			$return = $a->config[$uid][$cat][$k];
		}

		return $return;
	}

	/**
	 * Sets a preloaded value in the App user config cache
	 *
	 * Accepts raw output from the pconfig table
	 *
	 * @param int    $uid
	 * @param string $cat
	 * @param string $k
	 * @param mixed  $v
	 */
	private function setPreloadedValue($uid, $cat, $k, $v)
	{
		$a = self::getApp();

		// Only arrays are serialized in database, so we have to unserialize sparingly
		$value = is_string($v) && preg_match("|^a:[0-9]+:{.*}$|s", $v) ? unserialize($v) : $v;

		$a->config[$uid][$cat][$k] = $value;
	}

	/**
	 * Deletes a preloaded value from the App user config cache
	 *
	 * @param int    $uid
	 * @param string $cat
	 * @param string $k
	 */
	private function deletePreloadedValue($uid, $cat, $k)
	{
		$a = self::getApp();

		if (isset($a->config[$uid][$cat][$k])) {
			unset($a->config[$uid][$cat][$k]);
		}
	}
}
