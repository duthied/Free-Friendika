<?php

namespace Friendica\Model\Config;

use Friendica\Database\Database;

/**
 * The DB-based model of (P-)Config values
 * Encapsulates db-calls in case of config queries
 */
abstract class DbaConfig
{
	/** @var Database */
	protected $dba;

	/**
	 * @param Database $dba The database connection of this model
	 */
	public function __construct(Database $dba)
	{
		$this->dba = $dba;
	}

	/**
	 * Checks if the model is currently connected
	 *
	 * @return bool
	 */
	public function isConnected()
	{
		return $this->dba->isConnected();
	}

	/**
	 * Formats a DB value to a config value
	 * - null   = The db-value isn't set
	 * - bool   = The db-value is either '0' or '1'
	 * - array  = The db-value is a serialized array
	 * - string = The db-value is a string
	 *
	 * Keep in mind that there aren't any numeric/integer config values in the database
	 *
	 * @param null|string $value
	 *
	 * @return null|array|string
	 */
	protected function toConfigValue($value)
	{
		if (!isset($value)) {
			return null;
		}

		switch (true) {
			// manage array value
			case preg_match("|^a:[0-9]+:{.*}$|s", $value):
				return unserialize($value);

			default:
				return $value;
		}
	}

	/**
	 * Formats a config value to a DB value (string)
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	protected function toDbValue($value)
	{
		// if not set, save an empty string
		if (!isset($value)) {
			return '';
		}

		switch (true) {
			// manage arrays
			case is_array($value):
				return serialize($value);

			default:
				return (string)$value;
		}
	}
}
