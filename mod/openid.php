<?php
/**
 * @file mod/openid.php
 */

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\Util\Strings;

function openid_content(App $a) {

	if (Config::get('system','no_openid')) {
		$a->internalRedirect();
	}

	Logger::log('mod_openid ' . print_r($_REQUEST,true), Logger::DATA);

	if (!empty($_GET['openid_mode']) && !empty($_SESSION['openid'])) {

		$openid = new LightOpenID($a->getHostName());

		if ($openid->validate()) {
			$authid = $openid->identity;

			if (empty($authid)) {
				Logger::log(L10n::t('OpenID protocol error. No ID returned.') . EOL);
				$a->internalRedirect();
			}

			// NOTE: we search both for normalised and non-normalised form of $authid
			//       because the normalization step was removed from setting
			//       mod/settings.php in 8367cad so it might have left mixed
			//       records in the user table
			//
			$condition = ['blocked' => false, 'account_expired' => false, 'account_removed' => false, 'verified' => true,
				'openid' => [$authid, Strings::normaliseOpenID($authid)]];
			$user  = DBA::selectFirst('user', [], $condition);
			if (DBA::isResult($user)) {

				// successful OpenID login

				unset($_SESSION['openid']);

				Session::setAuthenticatedForUser($a, $user, true, true);

				// just in case there was no return url set
				// and we fell through

				$a->internalRedirect();
			}

			// Successful OpenID login - but we can't match it to an existing account.
			// New registration?

			if (intval(Config::get('config', 'register_policy')) === \Friendica\Module\Register::CLOSED) {
				notice(L10n::t('Account not found and OpenID registration is not permitted on this site.') . EOL);
				$a->internalRedirect();
			}

			unset($_SESSION['register']);
			$args = '';
			$attr = $openid->getAttributes();
			if (is_array($attr) && count($attr)) {
				foreach ($attr as $k => $v) {
					if ($k === 'namePerson/friendly') {
						$nick = Strings::escapeTags(trim($v));
					}
					if ($k === 'namePerson/first') {
						$first = Strings::escapeTags(trim($v));
					}
					if ($k === 'namePerson') {
						$args .= '&username=' . urlencode(Strings::escapeTags(trim($v)));
					}
					if ($k === 'contact/email') {
						$args .= '&email=' . urlencode(Strings::escapeTags(trim($v)));
					}
					if ($k === 'media/image/aspect11') {
						$photosq = bin2hex(trim($v));
					}
					if ($k === 'media/image/default') {
						$photo = bin2hex(trim($v));
					}
				}
			}
			if (!empty($nick)) {
				$args .= '&nickname=' . urlencode($nick);
			} elseif (!empty($first)) {
				$args .= '&nickname=' . urlencode($first);
			}

			if (!empty($photosq)) {
				$args .= '&photo=' . urlencode($photosq);
			} elseif (!empty($photo)) {
				$args .= '&photo=' . urlencode($photo);
			}

			$args .= '&openid_url=' . urlencode(Strings::escapeTags(trim($authid)));

			$a->internalRedirect('register?' . $args);

			// NOTREACHED
		}
	}
	notice(L10n::t('Login failed.') . EOL);
	$a->internalRedirect();
	// NOTREACHED
}
