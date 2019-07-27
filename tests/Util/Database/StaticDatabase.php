<?php

namespace Friendica\Test\Util\Database;

use Friendica\Database\Database;
use PDO;
use PDOException;

/**
 * Overrides the Friendica database class for re-using the connection
 * for different tests
 */
class StaticDatabase extends Database
{
	/**
	 * @var ExtendedPDO
	 */
	private static $staticConnection;

	/**
	 * Override the behaviour of connect, due there is just one, static connection at all
	 *
	 * @return bool|void
	 */
	public function connect()
	{
		if (!is_null($this->connection) && $this->connected()) {
			return true;
		}

		if (!isset(self::$staticConnection)) {

			$port       = 0;
			$serveraddr = trim($this->configCache->get('database', 'hostname'));
			$serverdata = explode(':', $serveraddr);
			$server     = $serverdata[0];
			if (count($serverdata) > 1) {
				$port = trim($serverdata[1]);
			}
			$server  = trim($server);
			$user    = trim($this->configCache->get('database', 'username'));
			$pass    = trim($this->configCache->get('database', 'password'));
			$db      = trim($this->configCache->get('database', 'database'));
			$charset = trim($this->configCache->get('database', 'charset'));

			if (!(strlen($server) && strlen($user))) {
				return false;
			}

			$connect = "mysql:host=" . $server . ";dbname=" . $db;

			if ($port > 0) {
				$connect .= ";port=" . $port;
			}

			if ($charset) {
				$connect .= ";charset=" . $charset;
			}


			try {
				self::$staticConnection = @new ExtendedPDO($connect, $user, $pass);
				self::$staticConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			} catch (PDOException $e) {
				/// @TODO At least log exception, don't ignore it!
			}
		}

		$this->driver = 'pdo';
		$this->connection = self::$staticConnection;
		$this->connected = true;

		return $this->connected;
	}

	/**
	 * Override the transaction since there are now hierachical transactions possible
	 *
	 * @return bool
	 */
	public function transaction()
	{
		if (!$this->connection->inTransaction() && !$this->connection->beginTransaction()) {
			return false;
		}

		$this->in_transaction = true;
		return true;
	}

	/**
	 * @brief Does a commit
	 *
	 * @return boolean Was the command executed successfully?
	 */
	public function commit()
	{
		if (!$this->performCommit()) {
			return false;
		}
		$this->in_transaction = false;
		return true;
	}

	/**
	 * @return ExtendedPDO The global, static connection
	 */
	public static function getGlobConnection()
	{
		return self::$staticConnection;
	}

	/**
	 * Perform a global commit for every nested transaction of the static connection
	 */
	public static function statCommit()
	{
		if (isset(self::$staticConnection)) {
			while (self::$staticConnection->getTransactionDepth() > 0) {
				self::$staticConnection->commit();
			}
		}
	}

	/**
	 * Perform a global rollback for every nested transaction of the static connection
	 */
	public static function statRollback()
	{
		if (isset(self::$staticConnection)) {
			while (self::$staticConnection->getTransactionDepth() > 0) {
				self::$staticConnection->rollBack();
			}
		}
	}
}
