<?php

namespace Friendica\Core\Config;

use Exception;
use Friendica\Database\DBA;

/**
 * Preload User Configuration Adapter
 *
 * Minimizes the number of database queries to retrieve configuration values at the cost of memory.
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class PreloadPConfigAdapter implements IPConfigAdapter
{
	private $config_loaded = false;

	/**
	 * The config cache of this adapter
	 * @var IPConfigCache
	 */
	private $configCache;

	/**
	 * @param IPConfigCache $configCache The config cache of this adapter
	 * @param int           $uid    The UID of the current user
	 */
	public function __construct(IPConfigCache $configCache, $uid = null)
	{
		$this->configCache = $configCache;
		if (isset($uid)) {
			$this->load($uid, 'config');
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function load($uid, $family)
	{
		if ($this->config_loaded) {
			return;
		}

		if (empty($uid)) {
			return;
		}

		$pconfigs = DBA::select('pconfig', ['cat', 'v', 'k'], ['uid' => $uid]);
		while ($pconfig = DBA::fetch($pconfigs)) {
			$this->configCache->setP($uid, $pconfig['cat'], $pconfig['k'], $pconfig['v']);
		}
		DBA::close($pconfigs);

		$this->config_loaded = true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($uid, $cat, $k, $default_value = null, $refresh = false)
	{
		if (!$this->config_loaded) {
			$this->load($uid, $cat);
		}

		if ($refresh) {
			$config = DBA::selectFirst('pconfig', ['v'], ['uid' => $uid, 'cat' => $cat, 'k' => $k]);
			if (DBA::isResult($config)) {
				$this->configCache->setP($uid, $cat, $k, $config['v']);
			} else {
				$this->configCache->deleteP($uid, $cat, $k);
			}
		}

		return $this->configCache->getP($uid, $cat, $k, $default_value);;
	}

	/**
	 * {@inheritdoc}
	 */
	public function set($uid, $cat, $k, $value)
	{
		if (!$this->config_loaded) {
			$this->load($uid, $cat);
		}
		// We store our setting values as strings.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$compare_value = !is_array($value) ? (string)$value : $value;

		if ($this->configCache->getP($uid, $cat, $k) === $compare_value) {
			return true;
		}

		$this->configCache->setP($uid, $cat, $k, $value);

		// manage array value
		$dbvalue = is_array($value) ? serialize($value) : $value;

		$result = DBA::update('pconfig', ['v' => $dbvalue], ['uid' => $uid, 'cat' => $cat, 'k' => $k], true);
		if (!$result) {
			throw new Exception('Unable to store config value in [' . $uid . '][' . $cat . '][' . $k . ']');
		}

		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete($uid, $cat, $k)
	{
		if (!$this->config_loaded) {
			$this->load($uid, $cat);
		}

		$this->configCache->deleteP($uid, $cat, $k);

		$result = DBA::delete('pconfig', ['uid' => $uid, 'cat' => $cat, 'k' => $k]);

		return $result;
	}
}
