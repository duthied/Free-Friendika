<?php

namespace Friendica\Module\Security;

use Friendica\App\Authentication;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Core\Config\Configuration;
use Friendica\Core\L10n\L10n;
use Friendica\Core\Session\ISession;
use Friendica\Database\Database;
use Friendica\Util\Strings;
use LightOpenID;
use Psr\Log\LoggerInterface;

/**
 * Performs an login with OpenID
 */
class OpenID extends BaseModule
{
	public static function content(array $parameters = [])
	{
		/** @var Configuration $config */
		$config = self::getClass(Configuration::class);

		/** @var BaseURL $baseUrl */
		$baseUrl = self::getClass(BaseURL::class);

		if ($config->get('system', 'no_openid')) {
			$baseUrl->redirect();
		}

		/** @var LoggerInterface $logger */
		$logger = self::getClass(LoggerInterface::class);

		$logger->debug('mod_openid.', ['request' => $_REQUEST]);

		/** @var ISession $session */
		$session = self::getClass(ISession::class);

		if (!empty($_GET['openid_mode']) && !empty($session->get('openid'))) {

			$openid = new LightOpenID($baseUrl->getHostname());

			/** @var L10n $l10n */
			$l10n = self::getClass(L10n::class);

			if ($openid->validate()) {
				$authId = $openid->data['openid_identity'];

				if (empty($authId)) {
					$logger->info($l10n->t('OpenID protocol error. No ID returned'));
					$baseUrl->redirect();
				}

				// NOTE: we search both for normalised and non-normalised form of $authid
				//       because the normalization step was removed from setting
				//       mod/settings.php in 8367cad so it might have left mixed
				//       records in the user table
				//
				$condition = ['blocked' => false, 'account_expired' => false, 'account_removed' => false, 'verified' => true,
				              'openid' => [$authId, Strings::normaliseOpenID($authId)]];

				$dba = self::getClass(Database::class);

				$user  = $dba->selectFirst('user', [], $condition);
				if ($dba->isResult($user)) {

					// successful OpenID login
					$session->remove('openid');

					/** @var Authentication $auth */
					$auth = self::getClass(Authentication::class);
					$auth->setForUser(self::getApp(), $user, true, true);

					// just in case there was no return url set
					// and we fell through
					$baseUrl->redirect();
				}

				// Successful OpenID login - but we can't match it to an existing account.
				$session->remove('register');
				$session->set('openid_attributes', $openid->getAttributes());
				$session->set('openid_identity', $authId);

				// Detect the server URL
				$open_id_obj = new LightOpenID($baseUrl->getHostName());
				$open_id_obj->identity = $authId;
				$session->set('openid_server', $open_id_obj->discover($open_id_obj->identity));

				if (intval($config->get('config', 'register_policy')) === \Friendica\Module\Register::CLOSED) {
					notice($l10n->t('Account not found. Please login to your existing account to add the OpenID to it.'));
				} else {
					notice($l10n->t('Account not found. Please register a new account or login to your existing account to add the OpenID to it.'));
				}

				$baseUrl->redirect('login');
			}
		}
	}
}
