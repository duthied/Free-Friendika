<?php

namespace Friendica\Model\Config;


/**
 * The Config model backend for users, which is using the general DB-model backend for user-configs
 */
class PConfig extends DbaConfig
{
	/**
	 * Loads all configuration values and returns the loaded category as an array.
	 *
	 * @param int         $uid The id of the user to load
	 * @param string|null $cat The category of the configuration values to load
	 *
	 * @return array The config array
	 *
	 * @throws \Exception In case DB calls are invalid
	 */
	public function load(int $uid, string $cat = null)
	{
		$return = [];

		if (empty($cat)) {
			$configs = $this->dba->select('pconfig', ['cat', 'v', 'k'], ['uid' => $uid]);
		} else {
			$configs = $this->dba->select('pconfig', ['cat', 'v', 'k'], ['cat' => $cat, 'uid' => $uid]);
		}

		while ($config = $this->dba->fetch($configs)) {
			$key   = $config['k'];
			$value = $this->toConfigValue($config['v']);

			// just save it in case it is set
			if (isset($value)) {
				$return[$config['cat']][$key] = $value;
			}
		}
		$this->dba->close($configs);

		return $return;
	}

	/**
	 * Get a particular user config variable out of the DB with the
	 * given category name ($cat) and a key ($key).
	 *
	 * Note: Boolean variables are defined as 0/1 in the database
	 *
	 * @param int         $uid The id of the user to load
	 * @param string $cat The category of the configuration value
	 * @param string $key The configuration key to query
	 *
	 * @return array|string|null Stored value or null if it does not exist
	 *
	 * @throws \Exception In case DB calls are invalid
	 */
	public function get(int $uid, string $cat, string $key)
	{
		if (!$this->isConnected()) {
			return null;
		}

		$config = $this->dba->selectFirst('pconfig', ['v'], ['uid' => $uid, 'cat' => $cat, 'k' => $key]);
		if ($this->dba->isResult($config)) {
			$value = $this->toConfigValue($config['v']);

			// just return it in case it is set
			if (isset($value)) {
				return $value;
			}
		}

		return null;
	}

	/**
	 * Stores a config value ($value) in the category ($cat) under the key ($key) for a
	 * given user ($uid).
	 *
	 * Note: Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param int    $uid   The id of the user to load
	 * @param string $cat   The category of the configuration value
	 * @param string $key   The configuration key to set
	 * @param mixed  $value The value to store
	 *
	 * @return bool Operation success
	 *
	 * @throws \Exception In case DB calls are invalid
	 */
	public function set(int $uid, string $cat, string $key, $value)
	{
		if (!$this->isConnected()) {
			return false;
		}

		// We store our setting values in a string variable.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$compare_value = (!is_array($value) ? (string)$value : $value);
		$stored_value  = $this->get($uid, $cat, $key);

		if (isset($stored_value) && ($stored_value === $compare_value)) {
			return true;
		}

		$dbvalue = $this->toDbValue($value);

		$result = $this->dba->update('pconfig', ['v' => $dbvalue], ['uid' => $uid, 'cat' => $cat, 'k' => $key], true);

		return $result;
	}

	/**
	 * Removes the configured value of the given user.
	 *
	 * @param int    $uid The id of the user to load
	 * @param string $cat The category of the configuration value
	 * @param string $key The configuration key to delete
	 *
	 * @return bool Operation success
	 *
	 * @throws \Exception In case DB calls are invalid
	 */
	public function delete(int $uid, string $cat, string $key)
	{
		if (!$this->isConnected()) {
			return false;
		}

		return $this->dba->delete('pconfig', ['uid' => $uid, 'cat' => $cat, 'k' => $key]);
	}
}
