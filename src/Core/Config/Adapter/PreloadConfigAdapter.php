<?php

namespace Friendica\Core\Config\Adapter;

use Friendica\Database\DBA;

/**
 * Preload Configuration Adapter
 *
 * Minimizes the number of database queries to retrieve configuration values at the cost of memory.
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class PreloadConfigAdapter extends AbstractDbaConfigAdapter implements IConfigAdapter
{
	private $config_loaded = false;

	/**
	 * {@inheritdoc}
	 */
	public function load($cat = 'config')
	{
		$return = [];

		if (!$this->isConnected()) {
			return $return;
		}

		if ($this->config_loaded) {
			return $return;
		}

		$configs = DBA::select('config', ['cat', 'v', 'k']);
		while ($config = DBA::fetch($configs)) {
			$return[$config['cat']][$config['k']] = $config['v'];
		}
		DBA::close($configs);

		$this->config_loaded = true;

		return $return;
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

			if (isset($value) && $value !== '') {
				return $value;
			}
		}

		return '!<unset>!';
	}

	/**
	 * {@inheritdoc}
	 */
	public function set($cat, $key, $value)
	{
		if (!$this->isConnected()) {
			return false;
		}

		// We store our setting values as strings.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$compare_value = !is_array($value) ? (string)$value : $value;

		if ($this->get($cat, $key) === $compare_value) {
			return true;
		}

		// manage array value
		$dbvalue = is_array($value) ? serialize($value) : $value;

		$result = DBA::update('config', ['v' => $dbvalue], ['cat' => $cat, 'k' => $key], true);

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

		return $this->config_loaded;
	}
}
