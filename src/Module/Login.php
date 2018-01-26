<?php
/**
 * @file src/Module/Login.php
 */
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Database\DBM;
use Friendica\Model\User;
use Friendica\Util\Network;
use Friendica\Util\Temporal;
use dba;
use Exception;
use LightOpenID;

require_once 'boot.php';
require_once 'include/datetime.php';
require_once 'include/security.php';
require_once 'include/text.php';

/**
 * Login module
 *
 * @author Hypolite Petovan mrpetovan@gmail.com
 */
class Login extends BaseModule
{
	public static function content()
	{
		$a = self::getApp();

		if (x($_SESSION, 'theme')) {
			unset($_SESSION['theme']);
		}

		if (x($_SESSION, 'mobile-theme')) {
			unset($_SESSION['mobile-theme']);
		}

		if (local_user()) {
			goaway(self::getApp()->get_baseurl());
		}

		return self::form(self::getApp()->get_baseurl(), $a->config['register_policy'] != REGISTER_CLOSED);
	}

	public static function post()
	{
		session_unset();
		// OpenId Login
		if (
			!x($_POST, 'password')
			&& (
				x($_POST, 'openid_url')
				|| x($_POST, 'username')
			)
		) {
			$noid = Config::get('system', 'no_openid');

			$openid_url = trim($_POST['openid_url'] ? : $_POST['username']);

			// if it's an email address or doesn't resolve to a URL, fail.
			if ($noid || strpos($openid_url, '@') || !Network::isUrlValid($openid_url)) {
				notice(L10n::t('Login failed.') . EOL);
				goaway(self::getApp()->get_baseurl());
				// NOTREACHED
			}

			// Otherwise it's probably an openid.
			try {
				$openid = new LightOpenID;
				$openid->identity = $openid_url;
				$_SESSION['openid'] = $openid_url;
				$_SESSION['remember'] = $_POST['remember'];
				$openid->returnUrl = self::getApp()->get_baseurl(true) . '/openid';
				goaway($openid->authUrl());
			} catch (Exception $e) {
				notice(L10n::t('We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.') . '<br /><br >' . L10n::t('The error message was:') . ' ' . $e->getMessage());
			}
			// NOTREACHED
		}

		if (x($_POST, 'auth-params') && $_POST['auth-params'] === 'login') {
			$record = null;

			$addon_auth = [
				'username' => trim($_POST['username']),
				'password' => trim($_POST['password']),
				'authenticated' => 0,
				'user_record' => null
			];

			/*
			 * An addon indicates successful login by setting 'authenticated' to non-zero value and returning a user record
			 * Addons should never set 'authenticated' except to indicate success - as hooks may be chained
			 * and later addons should not interfere with an earlier one that succeeded.
			 */
			Addon::callHooks('authenticate', $addon_auth);

			if ($addon_auth['authenticated'] && count($addon_auth['user_record'])) {
				$record = $addon_auth['user_record'];
			} else {
				$user_id = User::authenticate(trim($_POST['username']), trim($_POST['password']));
				if ($user_id) {
					$record = dba::selectFirst('user', [], ['uid' => $user_id]);
				}
			}

			if (!$record || !count($record)) {
				logger('authenticate: failed login attempt: ' . notags(trim($_POST['username'])) . ' from IP ' . $_SERVER['REMOTE_ADDR']);
				notice(L10n::t('Login failed.') . EOL);
				goaway(self::getApp()->get_baseurl());
			}

			if (!$_POST['remember']) {
				new_cookie(0); // 0 means delete on browser exit
			}

			// if we haven't failed up this point, log them in.
			$_SESSION['remember'] = $_POST['remember'];
			$_SESSION['last_login_date'] = Temporal::utcNow();
			authenticate_success($record, true, true);

			if (x($_SESSION, 'return_url')) {
				$return_url = $_SESSION['return_url'];
				unset($_SESSION['return_url']);
			} else {
				$return_url = '';
			}

			goaway($return_url);
		}
	}

	/**
	 * @brief Tries to auth the user from the cookie or session
	 *
	 * @todo Should be moved to Friendica\Core\Session when it's created
	 */
	public static function sessionAuth()
	{
		// When the "Friendica" cookie is set, take the value to authenticate and renew the cookie.
		if (isset($_COOKIE["Friendica"])) {
			$data = json_decode($_COOKIE["Friendica"]);
			if (isset($data->uid)) {

				$user = dba::selectFirst('user', [],
					[
						'uid'             => $data->uid,
						'blocked'         => false,
						'account_expired' => false,
						'account_removed' => false,
						'verified'        => true,
					]
				);
				if (DBM::is_result($user)) {
					if ($data->hash != cookie_hash($user)) {
						logger("Hash for user " . $data->uid . " doesn't fit.");
						nuke_session();
						goaway(self::getApp()->get_baseurl());
					}

					// Renew the cookie
					// Expires after 7 days by default,
					// can be set via system.auth_cookie_lifetime
					$authcookiedays = Config::get('system', 'auth_cookie_lifetime', 7);
					new_cookie($authcookiedays * 24 * 60 * 60, $user);

					// Do the authentification if not done by now
					if (!isset($_SESSION) || !isset($_SESSION['authenticated'])) {
						authenticate_success($user);

						if (Config::get('system', 'paranoia')) {
							$_SESSION['addr'] = $data->ip;
						}
					}
				}
			}
		}

		if (isset($_SESSION) && x($_SESSION, 'authenticated')) {
			if (x($_SESSION, 'visitor_id') && !x($_SESSION, 'uid')) {
				$r = q("SELECT * FROM `contact` WHERE `id` = %d LIMIT 1",
					intval($_SESSION['visitor_id'])
				);
				if (DBM::is_result($r)) {
					self::getApp()->contact = $r[0];
				}
			}

			if (x($_SESSION, 'uid')) {
				// already logged in user returning
				$check = Config::get('system', 'paranoia');
				// extra paranoia - if the IP changed, log them out
				if ($check && ($_SESSION['addr'] != $_SERVER['REMOTE_ADDR'])) {
					logger('Session address changed. Paranoid setting in effect, blocking session. ' .
						$_SESSION['addr'] . ' != ' . $_SERVER['REMOTE_ADDR']);
					nuke_session();
					goaway(self::getApp()->get_baseurl());
				}

				$user = dba::selectFirst('user', [],
					[
						'uid'             => $_SESSION['uid'],
						'blocked'         => false,
						'account_expired' => false,
						'account_removed' => false,
						'verified'        => true,
					]
				);
				if (!DBM::is_result($user)) {
					nuke_session();
					goaway(self::getApp()->get_baseurl());
				}

				// Make sure to refresh the last login time for the user if the user
				// stays logged in for a long time, e.g. with "Remember Me"
				$login_refresh = false;
				if (!x($_SESSION['last_login_date'])) {
					$_SESSION['last_login_date'] = Temporal::utcNow();
				}
				if (strcmp(Temporal::convert('now - 12 hours'), $_SESSION['last_login_date']) > 0) {
					$_SESSION['last_login_date'] = Temporal::utcNow();
					$login_refresh = true;
				}
				authenticate_success($user, false, false, $login_refresh);
			}
		}
	}

	/**
	 * @brief Wrapper for adding a login box.
	 *
	 * @param string $return_url The url relative to the base the user should be sent
	 *							 back to after login completes
	 * @param bool $register If $register == true provide a registration link.
	 *						 This will most always depend on the value of $a->config['register_policy'].
	 * @param array $hiddens  optional
	 *
	 * @return string Returns the complete html for inserting into the page
	 *
	 * @hooks 'login_hook' string $o
	 */
	public static function form($return_url = null, $register = false, $hiddens = [])
	{
		$a = self::getApp();
		$o = '';
		$reg = false;
		if ($register) {
			$reg = [
				'title' => L10n::t('Create a New Account'),
				'desc' => L10n::t('Register')
			];
		}

		$noid = Config::get('system', 'no_openid');

		if (is_null($return_url)) {
			$return_url = $a->query_string;
		}

		if (local_user()) {
			$tpl = get_markup_template('logout.tpl');
		} else {
			$a->page['htmlhead'] .= replace_macros(
				get_markup_template('login_head.tpl'),
				[
					'$baseurl' => $a->get_baseurl(true)
				]
			);

			$tpl = get_markup_template('login.tpl');
			$_SESSION['return_url'] = $return_url;
		}

		$o .= replace_macros(
			$tpl,
			[
				'$dest_url'     => self::getApp()->get_baseurl(true) . '/login',
				'$logout'       => L10n::t('Logout'),
				'$login'        => L10n::t('Login'),

				'$lname'        => ['username', L10n::t('Nickname or Email: ') , '', ''],
				'$lpassword'    => ['password', L10n::t('Password: '), '', ''],
				'$lremember'    => ['remember', L10n::t('Remember me'), 0,  ''],

				'$openid'       => !$noid,
				'$lopenid'      => ['openid_url', L10n::t('Or login using OpenID: '),'',''],

				'$hiddens'      => $hiddens,

				'$register'     => $reg,

				'$lostpass'     => L10n::t('Forgot your password?'),
				'$lostlink'     => L10n::t('Password Reset'),

				'$tostitle'     => L10n::t('Website Terms of Service'),
				'$toslink'      => L10n::t('terms of service'),

				'$privacytitle' => L10n::t('Website Privacy Policy'),
				'$privacylink'  => L10n::t('privacy policy'),
			]
		);

		Addon::callHooks('login_hook', $o);

		return $o;
	}
}
