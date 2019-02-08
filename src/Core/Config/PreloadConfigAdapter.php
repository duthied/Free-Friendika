<?php

namespace Friendica\Core\Config;

use Exception;
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
	 * @var IConfigCache The config cache of this driver
	 */
	private $configCache;

	/**
	 * @param IConfigCache $configCache The config cache of this driver
	 */
	public function __construct(IConfigCache $configCache)
	{
		$this->configCache = $configCache;
		$this->load();
	}

	/**
	 * {@inheritdoc}
	 */
	public function load($family = 'config')
	{
		if (!$this->isConnected()) {
			return;
		}

		if ($this->config_loaded) {
			return;
		}

		$configs = DBA::select('config', ['cat', 'v', 'k']);
		while ($config = DBA::fetch($configs)) {
			$this->configCache->set($config['cat'], $config['k'], $config['v']);
		}
		DBA::close($configs);

		$this->config_loaded = true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($cat, $k, $default_value = null, $refresh = false)
	{
		if (!$this->isConnected()) {
			return $default_value;
		}

		if ($refresh) {
			$config = DBA::selectFirst('config', ['v'], ['cat' => $cat, 'k' => $k]);
			if (DBA::isResult($config)) {
				$this->configCache->set($cat, $k, $config['v']);
			}
		}

		$return = $this->configCache->get($cat, $k, $default_value);

		return $return;
	}

	/**
	 * {@inheritdoc}
	 */
	public function set($cat, $k, $value)
	{
		if (!$this->isConnected()) {
			return false;
		}

		// We store our setting values as strings.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$compare_value = !is_array($value) ? (string)$value : $value;

		if ($this->configCache->get($cat, $k) === $compare_value) {
			return true;
		}

		$this->configCache->set($cat, $k, $value);

		// manage array value
		$dbvalue = is_array($value) ? serialize($value) : $value;

		$result = DBA::update('config', ['v' => $dbvalue], ['cat' => $cat, 'k' => $k], true);
		if (!$result) {
			throw new Exception('Unable to store config value in [' . $cat . '][' . $k . ']');
		}

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete($cat, $k)
	{
		if (!$this->isConnected()) {
			return false;
		}

		$this->configCache->delete($cat, $k);

		$result = DBA::delete('config', ['cat' => $cat, 'k' => $k]);

		return $result;
	}
}
