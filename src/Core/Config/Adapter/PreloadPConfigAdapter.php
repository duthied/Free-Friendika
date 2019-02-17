<?php

namespace Friendica\Core\Config\Adapter;

use Friendica\Database\DBA;

/**
 * Preload User Configuration Adapter
 *
 * Minimizes the number of database queries to retrieve configuration values at the cost of memory.
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class PreloadPConfigAdapter extends AbstractDbaConfigAdapter implements IPConfigAdapter
{
	private $config_loaded = false;

	/**
	 * @param int $uid The UID of the current user
	 */
	public function __construct($uid = null)
	{
		parent::__construct();

		if (isset($uid)) {
			$this->load($uid, 'config');
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function load($uid, $cat)
	{
		$return = [];

		if ($this->config_loaded) {
			return $return;
		}

		if (empty($uid)) {
			return $return;
		}

		$pconfigs = DBA::select('pconfig', ['cat', 'v', 'k'], ['uid' => $uid]);
		while ($pconfig = DBA::fetch($pconfigs)) {
			$return[$pconfig['cat']][$pconfig['k']] = $pconfig['v'];
		}
		DBA::close($pconfigs);

		$this->config_loaded = true;

		return $return;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get($uid, $cat, $key)
	{
		if (!$this->isConnected()) {
			return '!<unset>!';
		}

		if (!$this->config_loaded) {
			$this->load($uid, $cat);
		}

		$config = DBA::selectFirst('pconfig', ['v'], ['uid' => $uid, 'cat' => $cat, 'k' => $key]);
		if (DBA::isResult($config)) {
			// manage array value
			$value = (preg_match("|^a:[0-9]+:{.*}$|s", $config['v']) ? unserialize($config['v']) : $config['v']);

			return $value;
		} else {
			return '!<unset>!';
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function set($uid, $cat, $key, $value)
	{
		if (!$this->isConnected()) {
			return false;
		}

		if (!$this->config_loaded) {
			$this->load($uid, $cat);
		}
		// We store our setting values as strings.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$compare_value = !is_array($value) ? (string)$value : $value;

		if ($this->get($uid, $cat, $key) === $compare_value) {
			return true;
		}

		// manage array value
		$dbvalue = is_array($value) ? serialize($value) : $value;

		$result = DBA::update('pconfig', ['v' => $dbvalue], ['uid' => $uid, 'cat' => $cat, 'k' => $key], true);

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete($uid, $cat, $key)
	{
		if (!$this->isConnected()) {
			return false;
		}

		if (!$this->config_loaded) {
			$this->load($uid, $cat);
		}

		$result = DBA::delete('pconfig', ['uid' => $uid, 'cat' => $cat, 'k' => $key]);

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isLoaded($uid, $cat, $key)
	{
		if (!$this->isConnected()) {
			return false;
		}

		return $this->config_loaded;
	}
}
