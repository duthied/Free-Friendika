<?php
namespace Friendica\Core\Config;

use Friendica\Database\DBA;

/**
 * JustInTime User Configuration Adapter
 *
 * Default PConfig Adapter. Provides the best performance for pages loading few configuration variables.
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class JITPConfigAdapter implements IPConfigAdapter
{
	private $in_db;

	/**
	 * The config cache of this adapter
	 * @var IPConfigCache
	 */
	private $config;

	/**
	 * @param IPConfigCache $config The config cache of this adapter
	 */
	public function __construct($config)
	{
		$this->config = $config;
	}

	public function load($uid, $cat)
	{
		$pconfigs = DBA::select('pconfig', ['v', 'k'], ['cat' => $cat, 'uid' => $uid]);
		if (DBA::isResult($pconfigs)) {
			while ($pconfig = DBA::fetch($pconfigs)) {
				$k = $pconfig['k'];

				$this->config->setP($uid, $cat, $k, $pconfig['v']);

				$this->in_db[$uid][$cat][$k] = true;
			}
		} else if ($cat != 'config') {
			// Negative caching
			$this->config->setP($uid, $cat, null, "!<unset>!");
		}
		DBA::close($pconfigs);
	}

	public function get($uid, $cat, $k, $default_value = null, $refresh = false)
	{
		if (!$refresh) {
			// Looking if the whole family isn't set
			if ($this->config->getP($uid, $cat) !== null) {
				if ($this->config->getP($uid, $cat) === '!<unset>!') {
					return $default_value;
				}
			}

			if ($this->config->getP($uid, $cat, $k) !== null) {
				if ($this->config->getP($uid, $cat, $k) === '!<unset>!') {
					return $default_value;
				}
				return $this->config->getP($uid, $cat, $k);
			}
		}

		$pconfig = DBA::selectFirst('pconfig', ['v'], ['uid' => $uid, 'cat' => $cat, 'k' => $k]);
		if (DBA::isResult($pconfig)) {
			$val = (preg_match("|^a:[0-9]+:{.*}$|s", $pconfig['v']) ? unserialize($pconfig['v']) : $pconfig['v']);

			$this->config->setP($uid, $cat, $k, $val);

			$this->in_db[$uid][$cat][$k] = true;

			return $val;
		} else {
			$this->config->setP($uid, $cat, $k, '!<unset>!');

			$this->in_db[$uid][$cat][$k] = false;

			return $default_value;
		}
	}

	public function set($uid, $cat, $k, $value)
	{
		// We store our setting values in a string variable.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$dbvalue = (!is_array($value) ? (string)$value : $value);

		$stored = $this->get($uid, $cat, $k, null, true);

		if (($stored === $dbvalue) && $this->in_db[$uid][$cat][$k]) {
			return true;
		}

		$this->config->setP($uid, $cat, $k, $value);

		// manage array value
		$dbvalue = (is_array($value) ? serialize($value) : $dbvalue);

		$result = DBA::update('pconfig', ['v' => $dbvalue], ['uid' => $uid, 'cat' => $cat, 'k' => $k], true);

		if ($result) {
			$this->in_db[$uid][$cat][$k] = true;
		}

		return $result;
	}

	public function delete($uid, $cat, $k)
	{
		$this->config->deleteP($uid, $cat, $k);

		if (!empty($this->in_db[$uid][$cat][$k])) {
			unset($this->in_db[$uid][$cat][$k]);
		}

		$result = DBA::delete('pconfig', ['uid' => $uid, 'cat' => $cat, 'k' => $k]);

		return $result;
	}
}
