<?php

/**
 * @file /src/Core/Authentication.php
 */

namespace Friendica\Core;

use Friendica\App;
use Friendica\BaseObject;
use Friendica\Network\HTTPException\ForbiddenException;

/**
 * Handle Authentification, Session and Cookies
 */
class Authentication extends BaseObject
{
	/**
	 * @brief Calculate the hash that is needed for the "Friendica" cookie
	 *
	 * @param array $user Record from "user" table
	 *
	 * @return string Hashed data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getCookieHashForUser($user)
	{
		return hash_hmac(
			"sha256",
			hash_hmac("sha256", $user["password"], $user["prvkey"]),
			Config::get("system", "site_prvkey")
		);
	}

	/**
	 * @brief Set the "Friendica" cookie
	 *
	 * @param int   $time
	 * @param array $user Record from "user" table
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static  function setCookie($time, $user = [])
	{
		if ($time != 0) {
			$time = $time + time();
		}

		if ($user) {
			$value = json_encode([
				"uid" => $user["uid"],
				"hash" => self::getCookieHashForUser($user),
				"ip" => ($_SERVER['REMOTE_ADDR'] ?? '') ?: '0.0.0.0'
			]);
		} else {
			$value = "";
		}

		setcookie("Friendica", $value, $time, "/", "", (Config::get('system', 'ssl_policy') == App\BaseURL::SSL_POLICY_FULL), true);
	}

	/**
	 * @brief Kills the "Friendica" cookie and all session data
	 */
	public static function deleteSession()
	{
		self::setCookie(-3600); // make sure cookie is deleted on browser close, as a security measure
		session_unset();
		session_destroy();
	}

	public static function twoFactorCheck($uid, App $a)
	{
		// Check user setting, if 2FA disabled return
		if (!PConfig::get($uid, '2fa', 'verified')) {
			return;
		}

		// Check current path, if 2fa authentication module return
		if ($a->argc > 0 && in_array($a->argv[0], ['2fa', 'view', 'help', 'api', 'proxy', 'logout'])) {
			return;
		}

		// Case 1: 2FA session present and valid: return
		if (Session::get('2fa')) {
			return;
		}

		// Case 2: No valid 2FA session: redirect to code verification page
		if ($a->isAjax()) {
			throw new ForbiddenException();
		} else {
			$a->internalRedirect('2fa');
		}
	}
}
