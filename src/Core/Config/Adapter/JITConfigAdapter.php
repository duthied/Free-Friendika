<?php
namespace Friendica\Core\Config\Adapter;

use Friendica\Database\DBA;

/**
 * JustInTime Configuration Adapter
 *
 * Default Config Adapter. Provides the best performance for pages loading few configuration variables.
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class JITConfigAdapter extends AbstractDbaConfigAdapter implements IConfigAdapter
{
	private $in_db;

	/**
	 * {@inheritdoc}
	 */
	public function load($cat = "config")
	{
		$return = [];

		if (!$this->isConnected()) {
			return $return;
		}

		// We don't preload "system" anymore.
		// This reduces the number of database reads a lot.
		if ($cat === 'system') {
			return $return;
		}

		$configs = DBA::select('config', ['v', 'k'], ['cat' => $cat]);
		while ($config = DBA::fetch($configs)) {
			$key = $config['k'];

			$return[$key] = $config['v'];
			$this->in_db[$cat][$key] = true;
		}
		DBA::close($configs);

		return [$cat => $config];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($cat, $key)
	{
		if (!$this->isConnected()) {
			return '!<unset>!';
		}

		$config = DBA::selectFirst('config', ['v'], ['cat' => $cat, 'k' => $key]);
		if (DBA::isResult($config)) {
			// manage array value
			$value = (preg_match("|^a:[0-9]+:{.*}$|s", $config['v']) ? unserialize($config['v']) : $config['v']);

			$this->in_db[$cat][$key] = true;
			return $value;
		} else {

			$this->in_db[$cat][$key] = false;
			return '!<unset>!';
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function set($cat, $key, $value)
	{
		if (!$this->isConnected()) {
			return false;
		}

		// We store our setting values in a string variable.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$dbvalue = (!is_array($value) ? (string)$value : $value);

		$stored = $this->get($cat, $key);

		if (!isset($this->in_db[$cat])) {
			$this->in_db[$cat] = [];
		}
		if (!isset($this->in_db[$cat][$key])) {
			$this->in_db[$cat][$key] = false;
		}

		if (($stored === $dbvalue) && $this->in_db[$cat][$key]) {
			return true;
		}

		// manage array value
		$dbvalue = (is_array($value) ? serialize($value) : $dbvalue);

		$result = DBA::update('config', ['v' => $dbvalue], ['cat' => $cat, 'k' => $key], true);

		$this->in_db[$cat][$key] = $result;

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete($cat, $key)
	{
		if (!$this->isConnected()) {
			return false;
		}

		if (isset($this->cache[$cat][$key])) {
			unset($this->in_db[$cat][$key]);
		}

		$result = DBA::delete('config', ['cat' => $cat, 'k' => $key]);

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isLoaded($cat, $key)
	{
		if (!$this->isConnected()) {
			return false;
		}

		return (isset($this->in_db[$cat][$key])) && $this->in_db[$cat][$key];
	}
}
