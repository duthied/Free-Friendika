<?php
namespace Friendica\Core\Config;

use dba;
use Friendica\BaseObject;
use Friendica\Database\DBM;

require_once 'include/dba.php';

/**
 * JustInTime ConfigAdapter
 *
 * Default Config Adapter. Provides the best performance for pages loading few configuration variables.
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class JITConfigAdapter extends BaseObject implements IConfigAdapter
{
	private $cache;
	private $in_db;

	public function load($cat = "config")
	{
		// We don't preload "system" anymore.
		// This reduces the number of database reads a lot.
		if ($cat === 'system') {
			return;
		}

		$a = self::getApp();

		$configs = dba::select('config', ['v', 'k'], ['cat' => $cat]);
		while ($config = dba::fetch($configs)) {
			$k = $config['k'];
			if ($cat === 'config') {
				$a->config[$k] = $config['v'];
			} else {
				$a->config[$cat][$k] = $config['v'];
				self::$cache[$cat][$k] = $config['v'];
				self::$in_db[$cat][$k] = true;
			}
		}
		dba::close($configs);
	}

	public function get($cat, $k, $default_value = null, $refresh = false)
	{
		$a = self::getApp();

		if (!$refresh) {
			// Do we have the cached value? Then return it
			if (isset($this->cache[$cat][$k])) {
				if ($this->cache[$cat][$k] === '!<unset>!') {
					return $default_value;
				} else {
					return $this->cache[$cat][$k];
				}
			}
		}

		$config = dba::selectFirst('config', ['v'], ['cat' => $cat, 'k' => $k]);
		if (DBM::is_result($config)) {
			// manage array value
			$value = (preg_match("|^a:[0-9]+:{.*}$|s", $config['v']) ? unserialize($config['v']) : $config['v']);

			// Assign the value from the database to the cache
			$this->cache[$cat][$k] = $value;
			$this->in_db[$cat][$k] = true;
			return $value;
		} elseif (isset($a->config[$cat][$k])) {
			// Assign the value (mostly) from the .htconfig.php to the cache
			$this->cache[$cat][$k] = $a->config[$cat][$k];
			$this->in_db[$cat][$k] = false;

			return $a->config[$cat][$k];
		}

		$this->cache[$cat][$k] = '!<unset>!';
		$this->in_db[$cat][$k] = false;

		return $default_value;
	}

	public function set($cat, $k, $value)
	{
		$a = self::getApp();

		// We store our setting values in a string variable.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$dbvalue = (!is_array($value) ? (string)$value : $value);

		$stored = $this->get($cat, $k, null, true);

		if (($stored === $dbvalue) && $this->in_db[$cat][$k]) {
			return true;
		}

		if ($cat === 'config') {
			$a->config[$k] = $dbvalue;
		} elseif ($cat != 'system') {
			$a->config[$cat][$k] = $dbvalue;
		}

		// Assign the just added value to the cache
		$this->cache[$cat][$k] = $dbvalue;

		// manage array value
		$dbvalue = (is_array($value) ? serialize($value) : $dbvalue);

		$result = dba::update('config', ['v' => $dbvalue], ['cat' => $cat, 'k' => $k], true);

		if ($result) {
			$this->in_db[$cat][$k] = true;
			return $value;
		}

		return $result;
	}

	public function delete($cat, $k)
	{
		if (isset($this->cache[$cat][$k])) {
			unset($this->cache[$cat][$k]);
			unset($this->in_db[$cat][$k]);
		}

		$result = dba::delete('config', ['cat' => $cat, 'k' => $k]);

		return $result;
	}
}
