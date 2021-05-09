<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Security;

use Exception;
use Friendica\App;
use Friendica\Core\Config\IConfig;
use Friendica\Core\PConfig\IPConfig;
use Friendica\Core\Hook;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Network\HTTPException;
use Friendica\Security\TwoFactor\Repository\TrustedBrowser;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\Strings;
use LightOpenID;
use Friendica\Core\L10n;
use Psr\Log\LoggerInterface;

/**
 * Handle Authentication, Session and Cookies
 */
class Authentication
{
	/** @var IConfig */
	private $config;
	/** @var App\Mode */
	private $mode;
	/** @var App\BaseURL */
	private $baseUrl;
	/** @var L10n */
	private $l10n;
	/** @var Database */
	private $dba;
	/** @var LoggerInterface */
	private $logger;
	/** @var User\Cookie */
	private $cookie;
	/** @var Session\ISession */
	private $session;
	/** @var IPConfig */
	private $pConfig;

	/**
	 * Authentication constructor.
	 *
	 * @param IConfig          $config
	 * @param App\Mode         $mode
	 * @param App\BaseURL      $baseUrl
	 * @param L10n             $l10n
	 * @param Database         $dba
	 * @param LoggerInterface  $logger
	 * @param User\Cookie      $cookie
	 * @param Session\ISession $session
	 * @param IPConfig         $pConfig
	 */
	public function __construct(IConfig $config, App\Mode $mode, App\BaseURL $baseUrl, L10n $l10n, Database $dba, LoggerInterface $logger, User\Cookie $cookie, Session\ISession $session, IPConfig $pConfig)
	{
		$this->config  = $config;
		$this->mode    = $mode;
		$this->baseUrl = $baseUrl;
		$this->l10n    = $l10n;
		$this->dba     = $dba;
		$this->logger  = $logger;
		$this->cookie  = $cookie;
		$this->session = $session;
		$this->pConfig = $pConfig;
	}

	/**
	 * Tries to auth the user from the cookie or session
	 *
	 * @param App   $a      The Friendica Application context
	 *
	 * @throws HttpException\InternalServerErrorException In case of Friendica internal exceptions
	 * @throws Exception In case of general exceptions (like SQL Grammar)
	 */
	public function withSession(App $a)
	{
		// When the "Friendica" cookie is set, take the value to authenticate and renew the cookie.
		if ($this->cookie->get('uid')) {
			$user = $this->dba->selectFirst(
				'user',
				[],
				[
					'uid'             => $this->cookie->get('uid'),
					'blocked'         => false,
					'account_expired' => false,
					'account_removed' => false,
					'verified'        => true,
				]
			);
			if ($this->dba->isResult($user)) {
				if (!$this->cookie->comparePrivateDataHash($this->cookie->get('hash'),
					$user['password'] ?? '',
					$user['prvkey'] ?? '')
				) {
					$this->logger->notice("Hash doesn't fit.", ['user' => $this->cookie->get('uid')]);
					$this->session->clear();
					$this->cookie->clear();
					$this->baseUrl->redirect();
				}

				// Renew the cookie
				$this->cookie->send();

				// Do the authentification if not done by now
				if (!$this->session->get('authenticated')) {
					$this->setForUser($a, $user);

					if ($this->config->get('system', 'paranoia')) {
						$this->session->set('addr', $this->cookie->get('ip'));
					}
				}
			}
		}

		if ($this->session->get('authenticated')) {
			if ($this->session->get('visitor_id') && !$this->session->get('uid')) {
				$contact = $this->dba->selectFirst('contact', [], ['id' => $this->session->get('visitor_id')]);
				if ($this->dba->isResult($contact)) {
					$a->contact = $contact;
				}
			}

			if ($this->session->get('uid')) {
				// already logged in user returning
				$check = $this->config->get('system', 'paranoia');
				// extra paranoia - if the IP changed, log them out
				if ($check && ($this->session->get('addr') != $_SERVER['REMOTE_ADDR'])) {
					$this->logger->notice('Session address changed. Paranoid setting in effect, blocking session. ', [
							'addr'        => $this->session->get('addr'),
							'remote_addr' => $_SERVER['REMOTE_ADDR']]
					);
					$this->session->clear();
					$this->baseUrl->redirect();
				}

				$user = $this->dba->selectFirst(
					'user',
					[],
					[
						'uid'             => $this->session->get('uid'),
						'blocked'         => false,
						'account_expired' => false,
						'account_removed' => false,
						'verified'        => true,
					]
				);
				if (!$this->dba->isResult($user)) {
					$this->session->clear();
					$this->baseUrl->redirect();
				}

				// Make sure to refresh the last login time for the user if the user
				// stays logged in for a long time, e.g. with "Remember Me"
				$login_refresh = false;
				if (!$this->session->get('last_login_date')) {
					$this->session->set('last_login_date', DateTimeFormat::utcNow());
				}
				if (strcmp(DateTimeFormat::utc('now - 12 hours'), $this->session->get('last_login_date')) > 0) {
					$this->session->set('last_login_date', DateTimeFormat::utcNow());
					$login_refresh = true;
				}

				$this->setForUser($a, $user, false, false, $login_refresh);
			}
		}
	}

	/**
	 * Attempts to authenticate using OpenId
	 *
	 * @param string $openid_url OpenID URL string
	 * @param bool   $remember   Whether to set the session remember flag
	 *
	 * @throws HttpException\InternalServerErrorException In case of Friendica internal exceptions
	 */
	public function withOpenId(string $openid_url, bool $remember)
	{
		$noid = $this->config->get('system', 'no_openid');

		// if it's an email address or doesn't resolve to a URL, fail.
		if ($noid || strpos($openid_url, '@') || !Network::isUrlValid($openid_url)) {
			notice($this->l10n->t('Login failed.'));
			$this->baseUrl->redirect();
		}

		// Otherwise it's probably an openid.
		try {
			$openid           = new LightOpenID($this->baseUrl->getHostname());
			$openid->identity = $openid_url;
			$this->session->set('openid', $openid_url);
			$this->session->set('remember', $remember);
			$openid->returnUrl = $this->baseUrl->get(true) . '/openid';
			$openid->optional  = ['namePerson/friendly', 'contact/email', 'namePerson', 'namePerson/first', 'media/image/aspect11', 'media/image/default'];
			System::externalRedirect($openid->authUrl());
		} catch (Exception $e) {
			notice($this->l10n->t('We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.') . '<br /><br >' . $this->l10n->t('The error message was:') . ' ' . $e->getMessage());
		}
	}

	/**
	 * Attempts to authenticate using login/password
	 *
	 * @param App    $a        The Friendica Application context
	 * @param string $username User name
	 * @param string $password Clear password
	 * @param bool   $remember Whether to set the session remember flag
	 *
	 * @throws HttpException\InternalServerErrorException In case of Friendica internal exceptions
	 * @throws Exception A general Exception (like SQL Grammar exceptions)
	 */
	public function withPassword(App $a, string $username, string $password, bool $remember)
	{
		$record = null;

		$addon_auth = [
			'username'      => $username,
			'password'      => $password,
			'authenticated' => 0,
			'user_record'   => null
		];

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
					throw new Exception($this->l10n->t('Login failed.'));
				}
			} else {
				$record = $this->dba->selectFirst(
					'user',
					[],
					['uid' => User::getIdFromPasswordAuthentication($username, $password)]
				);
			}
		} catch (Exception $e) {
			$this->logger->warning('authenticate: failed login attempt', ['action' => 'login', 'username' => Strings::escapeTags($username), 'ip' => $_SERVER['REMOTE_ADDR']]);
			notice($this->l10n->t('Login failed. Please check your credentials.'));
			$this->baseUrl->redirect();
		}

		if (!$remember) {
			$this->cookie->clear();
		}

		// if we haven't failed up this point, log them in.
		$this->session->set('remember', $remember);
		$this->session->set('last_login_date', DateTimeFormat::utcNow());

		$openid_identity = $this->session->get('openid_identity');
		$openid_server   = $this->session->get('openid_server');

		if (!empty($openid_identity) || !empty($openid_server)) {
			$this->dba->update('user', ['openid' => $openid_identity, 'openidserver' => $openid_server], ['uid' => $record['uid']]);
		}

		$this->setForUser($a, $record, true, true);

		$return_path = $this->session->get('return_path', '');
		$this->session->remove('return_path');

		$this->baseUrl->redirect($return_path);
	}

	/**
	 * Sets the provided user's authenticated session
	 *
	 * @param App   $a           The Friendica application context
	 * @param array $user_record The current "user" record
	 * @param bool  $login_initial
	 * @param bool  $interactive
	 * @param bool  $login_refresh
	 *
	 * @throws HTTPException\InternalServerErrorException In case of Friendica specific exceptions
	 * @throws Exception In case of general Exceptions (like SQL Grammar exceptions)
	 */
	public function setForUser(App $a, array $user_record, bool $login_initial = false, bool $interactive = false, bool $login_refresh = false)
	{
		$this->session->setMultiple([
			'uid'           => $user_record['uid'],
			'theme'         => $user_record['theme'],
			'mobile-theme'  => $this->pConfig->get($user_record['uid'], 'system', 'mobile_theme'),
			'authenticated' => 1,
			'page_flags'    => $user_record['page-flags'],
			'my_url'        => $this->baseUrl->get() . '/profile/' . $user_record['nickname'],
			'my_address'    => $user_record['nickname'] . '@' . substr($this->baseUrl->get(), strpos($this->baseUrl->get(), '://') + 3),
			'addr'          => ($_SERVER['REMOTE_ADDR'] ?? '') ?: '0.0.0.0'
		]);

		Session::setVisitorsContacts();

		$member_since = strtotime($user_record['register_date']);
		$this->session->set('new_member', time() < ($member_since + (60 * 60 * 24 * 14)));

		if (strlen($user_record['timezone'])) {
			date_default_timezone_set($user_record['timezone']);
			$a->timezone = $user_record['timezone'];
		}

		$masterUid = $user_record['uid'];

		if ($this->session->get('submanage')) {
			$user = $this->dba->selectFirst('user', ['uid'], ['uid' => $this->session->get('submanage')]);
			if ($this->dba->isResult($user)) {
				$masterUid = $user['uid'];
			}
		}

		$a->identities = User::identities($masterUid);

		if ($login_initial) {
			$this->logger->info('auth_identities: ' . print_r($a->identities, true));
		}

		if ($login_refresh) {
			$this->logger->info('auth_identities refresh: ' . print_r($a->identities, true));
		}

		$contact = $this->dba->selectFirst('contact', [], ['uid' => $user_record['uid'], 'self' => true]);
		if ($this->dba->isResult($contact)) {
			$a->contact = $contact;
			$a->cid     = $contact['id'];
			$this->session->set('cid', $a->cid);
		}

		header('X-Account-Management-Status: active; name="' . $user_record['username'] . '"; id="' . $user_record['nickname'] . '"');

		if ($login_initial || $login_refresh) {
			$this->dba->update('user', ['login_date' => DateTimeFormat::utcNow()], ['uid' => $user_record['uid']]);

			// Set the login date for all identities of the user
			$this->dba->update('user', ['login_date' => DateTimeFormat::utcNow()],
				['parent-uid' => $masterUid, 'account_removed' => false]);
		}

		if ($login_initial) {
			/*
			 * If the user specified to remember the authentication, then set a cookie
			 * that expires after one week (the default is when the browser is closed).
			 * The cookie will be renewed automatically.
			 * The week ensures that sessions will expire after some inactivity.
			 */
			if ($this->session->get('remember')) {
				$this->logger->info('Injecting cookie for remembered user ' . $user_record['nickname']);
				$this->cookie->setMultiple([
					'uid'  => $user_record['uid'],
					'hash' => $this->cookie->hashPrivateData($user_record['password'], $user_record['prvkey']),
				]);
				$this->session->remove('remember');
			}
		}

		$this->redirectForTwoFactorAuthentication($user_record['uid'], $a);

		if ($interactive) {
			if ($user_record['login_date'] <= DBA::NULL_DATETIME) {
				info($this->l10n->t('Welcome %s', $user_record['username']));
				info($this->l10n->t('Please upload a profile photo.'));
				$this->baseUrl->redirect('settings/profile/photo/new');
			}
		}

		$a->user = $user_record;

		if ($login_initial) {
			Hook::callAll('logged_in', $a->user);

			if (DI::module()->getName() !== 'home' && $this->session->exists('return_path')) {
				$this->baseUrl->redirect($this->session->get('return_path'));
			}
		}
	}

	/**
	 * Decides whether to redirect the user to two-factor authentication.
	 * All return calls in this method skip two-factor authentication
	 *
	 * @param int $uid The User Identified
	 * @param App $a   The Friendica Application context
	 *
	 * @throws HTTPException\ForbiddenException In case the two factor authentication is forbidden (e.g. for AJAX calls)
	 * @throws HTTPException\InternalServerErrorException
	 */
	private function redirectForTwoFactorAuthentication(int $uid, App $a)
	{
		// Check user setting, if 2FA disabled return
		if (!$this->pConfig->get($uid, '2fa', 'verified')) {
			return;
		}

		// Check current path, if public or 2fa module return
		if ($a->argc > 0 && in_array($a->argv[0], ['2fa', 'view', 'help', 'api', 'proxy', 'logout'])) {
			return;
		}

		// Case 1a: 2FA session already present: return
		if ($this->session->get('2fa')) {
			return;
		}

		// Case 1b: Check for trusted browser
		if ($this->cookie->get('trusted')) {
			// Retrieve a trusted_browser model based on cookie hash
			$trustedBrowserRepository = new TrustedBrowser($this->dba, $this->logger);
			try {
				$trustedBrowser = $trustedBrowserRepository->selectOneByHash($this->cookie->get('trusted'));
				// Verify record ownership
				if ($trustedBrowser->uid === $uid) {
					// Update last_used date
					$trustedBrowser->recordUse();

					// Save it to the database
					$trustedBrowserRepository->save($trustedBrowser);

					// Set 2fa session key and return
					$this->session->set('2fa', true);

					return;
				} else {
					// Invalid trusted cookie value, removing it
					$this->cookie->unset('trusted');
				}
			} catch (\Throwable $e) {
				// Local trusted browser record was probably removed by the user, we carry on with 2FA
			}
		}

		// Case 2: No valid 2FA session: redirect to code verification page
		if ($this->mode->isAjax()) {
			throw new HTTPException\ForbiddenException();
		} else {
			$this->baseUrl->redirect('2fa');
		}
	}
}
