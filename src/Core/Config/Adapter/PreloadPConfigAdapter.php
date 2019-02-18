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
	/**
	 * @var array true if config for user is loaded
	 */
	private $config_loaded;

	/**
	 * @param int $uid The UID of the current user
	 */
	public function __construct($uid = null)
	{
		parent::__construct();

		if (isset($uid)) {
			$this->load($uid, 'config');
		}

		$this->config_loaded = [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function load($uid, $cat)
	{
		$return = [];

		if (empty($uid)) {
			return $return;
		}

		if ($this->isLoaded($uid, $cat, null)) {
			return $return;
		}

		$pconfigs = DBA::select('pconfig', ['cat', 'v', 'k'], ['uid' => $uid]);
		while ($pconfig = DBA::fetch($pconfigs)) {
			$value = $pconfig['v'];
			if (isset($value) && $value !== '') {
				$return[$pconfig['cat']][$pconfig['k']] = $value;
			} else {
				$return[$pconfig['cat']][$pconfig['k']] = '!<unset>!';
			}
		}
		DBA::close($pconfigs);

		$this->config_loaded[$uid] = true;

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

		if (!$this->isLoaded($uid, $cat, $key)) {
			$this->load($uid, $cat);
		}

		$config = DBA::selectFirst('pconfig', ['v'], ['uid' => $uid, 'cat' => $cat, 'k' => $key]);
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
	public function set($uid, $cat, $key, $value)
	{
		if (!$this->isConnected()) {
			return false;
		}

		if ($this->isLoaded($uid, $cat, $key)) {
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

		if (!$this->isLoaded($uid, $cat, $key)) {
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

		return isset($this->config_loaded[$uid]) && $this->config_loaded[$uid];
	}
}
