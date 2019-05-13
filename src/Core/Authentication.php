<?php
/**
 * @file /src/Core/Authentication.php
 */

namespace Friendica\Core;

use Friendica\BaseObject;
use Friendica\Util\BaseURL;

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
		return(hash("sha256", Config::get("system", "site_prvkey") .
				$user["prvkey"] .
				$user["password"]));
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
			$value = json_encode(["uid" => $user["uid"],
				"hash" => self::getCookieHashForUser($user),
				"ip" => defaults($_SERVER, 'REMOTE_ADDR', '0.0.0.0')]);
		} else {
			$value = "";
		}

		setcookie("Friendica", $value, $time, "/", "", (Config::get('system', 'ssl_policy') == BaseUrl::SSL_POLICY_FULL), true);
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
}

