<?php

/**
 * @file /src/Core/Authentication.php
 */

namespace Friendica\Core;

use Friendica\App;
use Friendica\BaseObject;
use Friendica\Database\DBA;
use Friendica\Model\User;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\Strings;

/**
 * Handle Authentification, Session and Cookies
 */
class Authentication extends BaseObject
{
	/**
	 * Attempts to authenticate using OpenId
	 *
	 * @param string $openid_url OpenID URL string
	 * @param bool   $remember   Whether to set the session remember flag
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function openIdAuthentication($openid_url, $remember)
	{
		$noid = Config::get('system', 'no_openid');

		$a = self::getApp();

		// if it's an email address or doesn't resolve to a URL, fail.
		if ($noid || strpos($openid_url, '@') || !Network::isUrlValid($openid_url)) {
			notice(L10n::t('Login failed.') . EOL);
			$a->internalRedirect();
			// NOTREACHED
		}

		// Otherwise it's probably an openid.
		try {
			$openid = new LightOpenID($a->getHostName());
			$openid->identity = $openid_url;
			Session::set('openid', $openid_url);
			Session::set('remember', $remember);
			$openid->returnUrl = $a->getBaseURL(true) . '/openid';
			$openid->optional = ['namePerson/friendly', 'contact/email', 'namePerson', 'namePerson/first', 'media/image/aspect11', 'media/image/default'];
			System::externalRedirect($openid->authUrl());
		} catch (Exception $e) {
			notice(L10n::t('We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.') . '<br /><br >' . L10n::t('The error message was:') . ' ' . $e->getMessage());
		}
	}

	/**
	 * Attempts to authenticate using login/password
	 *
	 * @param string $username        User name
	 * @param string $password        Clear password
	 * @param bool   $remember        Whether to set the session remember flag
	 * @param string $openid_identity OpenID identity
	 * @param string $openid_server   OpenID URL
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function passwordAuthentication($username, $password, $remember, $openid_identity, $openid_server)
	{
		$record = null;

		$addon_auth = [
			'username' => $username,
			'password' => $password,
			'authenticated' => 0,
			'user_record' => null
		];

		$a = self::getApp();

		/*
		 * An addon indicates successful login by setting 'authenticated' to non-zero value and returning a user record
		 * Addons should never set 'authenticated' except to indicate success - as hooks may be chained
		 * and later addons should not interfere with an earlier one that succeeded.
		 */
		Hook::callAll('authenticate', $addon_auth);

		try {
			if ($addon_auth['authenticated']) {
				$record = $addon_auth['user_record'];

				if (empty($record)) {
					throw new Exception(L10n::t('Login failed.'));
				}
			} else {
				$record = DBA::selectFirst(
					'user',
					[],
					['uid' => User::getIdFromPasswordAuthentication($username, $password)]
				);
			}
		} catch (Exception $e) {
			Logger::warning('authenticate: failed login attempt', ['action' => 'login', 'username' => Strings::escapeTags($username), 'ip' => $_SERVER['REMOTE_ADDR']]);
			info('Login failed. Please check your credentials.' . EOL);
			$a->internalRedirect();
		}

		if (!$remember) {
			Authentication::setCookie(0); // 0 means delete on browser exit
		}

		// if we haven't failed up this point, log them in.
		Session::set('remember', $remember);
		Session::set('last_login_date', DateTimeFormat::utcNow());

		if (!empty($openid_identity) || !empty($openid_server)) {
			DBA::update('user', ['openid' => $openid_identity, 'openidserver' => $openid_server], ['uid' => $record['uid']]);
		}

		Session::setAuthenticatedForUser($a, $record, true, true);

		$return_path = Session::get('return_path', '');
		Session::remove('return_path');

		$a->internalRedirect($return_path);
	}

	/**
	 * @brief Tries to auth the user from the cookie or session
	 *
	 * @todo Should be moved to Friendica\Core\Session when it's created
	 */
	public static function sessionAuth()
	{
		$a = self::getApp();

		// When the "Friendica" cookie is set, take the value to authenticate and renew the cookie.
		if (isset($_COOKIE["Friendica"])) {
			$data = json_decode($_COOKIE["Friendica"]);
			if (isset($data->uid)) {

				$user = DBA::selectFirst(
					'user',
					[],
					[
						'uid'             => $data->uid,
						'blocked'         => false,
						'account_expired' => false,
						'account_removed' => false,
						'verified'        => true,
					]
				);
				if (DBA::isResult($user)) {
					if (!hash_equals(
						Authentication::getCookieHashForUser($user),
						$data->hash
					)) {
						Logger::log("Hash for user " . $data->uid . " doesn't fit.");
						Authentication::deleteSession();
						$a->internalRedirect();
					}

					// Renew the cookie
					// Expires after 7 days by default,
					// can be set via system.auth_cookie_lifetime
					$authcookiedays = Config::get('system', 'auth_cookie_lifetime', 7);
					Authentication::setCookie($authcookiedays * 24 * 60 * 60, $user);

					// Do the authentification if not done by now
					if (!isset($_SESSION) || !isset($_SESSION['authenticated'])) {
						Session::setAuthenticatedForUser($a, $user);

						if (Config::get('system', 'paranoia')) {
							$_SESSION['addr'] = $data->ip;
						}
					}
				}
			}
		}

		if (!empty($_SESSION['authenticated'])) {
			if (!empty($_SESSION['visitor_id']) && empty($_SESSION['uid'])) {
				$contact = DBA::selectFirst('contact', [], ['id' => $_SESSION['visitor_id']]);
				if (DBA::isResult($contact)) {
					self::getApp()->contact = $contact;
				}
			}

			if (!empty($_SESSION['uid'])) {
				// already logged in user returning
				$check = Config::get('system', 'paranoia');
				// extra paranoia - if the IP changed, log them out
				if ($check && ($_SESSION['addr'] != $_SERVER['REMOTE_ADDR'])) {
					Logger::log('Session address changed. Paranoid setting in effect, blocking session. ' .
					            $_SESSION['addr'] . ' != ' . $_SERVER['REMOTE_ADDR']);
					Authentication::deleteSession();
					$a->internalRedirect();
				}

				$user = DBA::selectFirst(
					'user',
					[],
					[
						'uid'             => $_SESSION['uid'],
						'blocked'         => false,
						'account_expired' => false,
						'account_removed' => false,
						'verified'        => true,
					]
				);
				if (!DBA::isResult($user)) {
					Authentication::deleteSession();
					$a->internalRedirect();
				}

				// Make sure to refresh the last login time for the user if the user
				// stays logged in for a long time, e.g. with "Remember Me"
				$login_refresh = false;
				if (empty($_SESSION['last_login_date'])) {
					$_SESSION['last_login_date'] = DateTimeFormat::utcNow();
				}
				if (strcmp(DateTimeFormat::utc('now - 12 hours'), $_SESSION['last_login_date']) > 0) {
					$_SESSION['last_login_date'] = DateTimeFormat::utcNow();
					$login_refresh = true;
				}

				Session::setAuthenticatedForUser($a, $user, false, false, $login_refresh);
			}
		}
	}

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
