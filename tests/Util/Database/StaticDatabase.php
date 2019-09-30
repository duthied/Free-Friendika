<?php

namespace Friendica\Test\Util\Database;

use Friendica\Database\Database;
use PDO;
use PDOException;

/**
 * Overrides the Friendica database class for re-using the connection
 * for different tests
 *
 * Overrides functionality to enforce one transaction per call (for nested transactions)
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
			self::statConnect($_SERVER);
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
		if (!$this->in_transaction && !$this->connection->beginTransaction()) {
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
	 * Setup of the global, static connection
	 * Either through explicit calling or through implicit using the Database
	 *
	 * @param array $server $_SERVER variables
	 */
	public static function statConnect(array $server)
	{
		// Use environment variables for mysql if they are set beforehand
		if (!empty($server['MYSQL_HOST'])
		    && (!empty($server['MYSQL_USERNAME'] || !empty($server['MYSQL_USER'])))
		    && $server['MYSQL_PASSWORD'] !== false
		    && !empty($server['MYSQL_DATABASE']))
		{
			$db_host = $server['MYSQL_HOST'];
			if (!empty($server['MYSQL_PORT'])) {
				$db_host .= ':' . $server['MYSQL_PORT'];
			}

			if (!empty($server['MYSQL_USERNAME'])) {
				$db_user = $server['MYSQL_USERNAME'];
			} else {
				$db_user = $server['MYSQL_USER'];
			}
			$db_pw = (string) $server['MYSQL_PASSWORD'];
			$db_data = $server['MYSQL_DATABASE'];
		}

		$port       = 0;
		$serveraddr = trim($db_host);
		$serverdata = explode(':', $serveraddr);
		$server     = $serverdata[0];
		if (count($serverdata) > 1) {
			$port = trim($serverdata[1]);
		}
		$server  = trim($server);
		$user    = trim($db_user);
		$pass    = trim($db_pw);
		$db      = trim($db_data);

		if (!(strlen($server) && strlen($user))) {
			return;
		}

		$connect = "mysql:host=" . $server . ";dbname=" . $db;

		if ($port > 0) {
			$connect .= ";port=" . $port;
		}

		try {
			self::$staticConnection = @new ExtendedPDO($connect, $user, $pass);
			self::$staticConnection->setAttribute(PDO::ATTR_AUTOCOMMIT,0);
		} catch (PDOException $e) {
			/// @TODO At least log exception, don't ignore it!
		}
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
