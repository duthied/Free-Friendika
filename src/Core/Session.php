<?php

/**
 * @file src/Core/Session.php
 */
namespace Friendica\Core;

use Friendica\Core\Session\DatabaseSessionHandler;
use Friendica\Core\Session\MemcacheSessionHandler;

/**
 * High-level Session service class
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class Session
{
	public static $exists = false;
	public static $expire = 180000;

	public static function init()
	{
		ini_set('session.gc_probability', 50);
		ini_set('session.use_only_cookies', 1);
		ini_set('session.cookie_httponly', 1);

		if (Config::get('system', 'ssl_policy') == SSL_POLICY_FULL) {
			ini_set('session.cookie_secure', 1);
		}

		if (!Config::get('system', 'disable_database_session')) {
			$memcache = Cache::memcache();
			if (is_object($memcache)) {
				$SessionHandler = new MemcacheSessionHandler($memcache);
			} else {
				$SessionHandler = new DatabaseSessionHandler();
			}

			session_set_save_handler($SessionHandler);
		}
	}

	public static function exists($name)
	{
		return isset($_SESSION[$name]);
	}

	public static function get($name)
	{
		return defaults($_SESSION, $name, null);
	}

	public static function set($name, $value)
	{
		$_SESSION[$name] = $value;
	}
}
