<?php

namespace Friendica\App;

use Friendica\Core\Config;
use Friendica\Database\DBA;

/**
 * Mode of the current Friendica Node
 *
 * @package Friendica\App
 */
class Mode
{
	const LOCALCONFIGPRESENT = 1;
	const DBAVAILABLE = 2;
	const DBCONFIGAVAILABLE = 4;
	const MAINTENANCEDISABLED = 8;

	/***
	 * @var int the mode of this Application
	 *
	 * Default is 0 (= not set)
	 */
	private static $mode = 0;

	/**
	 * Sets the App mode
	 *
	 * - App::MODE_INSTALL    : Either the database connection can't be established or the config table doesn't exist
	 * - App::MODE_MAINTENANCE: The maintenance mode has been set
	 * - App::MODE_NORMAL     : Normal run with all features enabled
	 *
	 * @param string $basepath the Basepath of the Application
	 *
	 */
	public static function determine($basepath)
	{
		self::$mode = 0;

		if (!file_exists($basepath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'local.ini.php')
			&& !file_exists($basepath . DIRECTORY_SEPARATOR . '.htconfig.php')) {
			return;
		}

		self::$mode |= Mode::LOCALCONFIGPRESENT;

		if (!DBA::connected()) {
			return;
		}

		self::$mode |= Mode::DBAVAILABLE;

		if (DBA::fetchFirst("SHOW TABLES LIKE 'config'") === false) {
			return;
		}

		self::$mode |= Mode::DBCONFIGAVAILABLE;

		if (Config::get('system', 'maintenance')) {
			return;
		}

		self::$mode |= Mode::MAINTENANCEDISABLED;
	}

	/**
	 * Checks, if the Friendica Node has the given mode
	 *
	 * @param int $mode A mode to test
	 *
	 * @return bool returns true, if the mode is set
	 */
	public static function has($mode)
	{
		return self::$mode & $mode;
	}


	/**
	 * Install mode is when the local config file is missing or the DB schema hasn't been installed yet.
	 *
	 * @return bool
	 */
	public static function isInstall()
	{
		return !self::has(Mode::LOCALCONFIGPRESENT) ||
			!self::has(MODE::DBCONFIGAVAILABLE);
	}

	/**
	 * Normal mode is when the local config file is set, the DB schema is installed and the maintenance mode is off.
	 *
	 * @return bool
	 */
	public static function isNormal()
	{
		return self::has(Mode::LOCALCONFIGPRESENT) &&
			self::has(Mode::DBAVAILABLE) &&
			self::has(Mode::DBCONFIGAVAILABLE) &&
			self::has(Mode::MAINTENANCEDISABLED);
	}
}
