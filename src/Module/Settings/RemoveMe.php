<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Module\Settings;

use Friendica\App;
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Model\User\Cookie;
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Emailer;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

class RemoveMe extends BaseSettings
{
	/** @var Emailer */
	private $emailer;
	/** @var SystemMessages */
	private $systemMessages;
	/** @var Cookie */
	private $cookie;

	public function __construct(Cookie $cookie, SystemMessages $systemMessages, Emailer $emailer, IHandleUserSessions $session, App\Page $page, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->emailer        = $emailer;
		$this->systemMessages = $systemMessages;
		$this->cookie         = $cookie;
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			return;
		}

		if ($this->session->getSubManagedUserId()) {
			return;
		}

		$hash = $this->session->pop('remove_account_verify');
		if (empty($hash) || empty($request[$hash])) {
			return;
		}

		try {
			$userId = User::getIdFromPasswordAuthentication($this->session->getLocalUserId(), trim($request[$hash]));
			if ($userId != $this->session->getLocalUserId()) {
				throw new \RuntimeException($this->t("There was a validation error, please make sure you're logged in with the account you want to remove and try again.") . ' ' . $this->t('If this error persists, please contact your administrator.'));
			}
		} catch (\Throwable $e) {
			$this->systemMessages->addNotice($e->getMessage());
			return;
		}

		// send notification to admins so that they can clean up the backups
		foreach (User::getAdminListForEmailing(['uid', 'language', 'email']) as $admin) {
			$l10n = $this->l10n->withLang($admin['language'] ?: 'en');

			$email = $this->emailer
				->newSystemMail()
				->withMessage(
					$l10n->t('[Friendica System Notify]') . ' ' . $l10n->t('User deleted their account'),
					$l10n->t('On your Friendica node an user deleted their account. Please ensure that their data is removed from the backups.'),
					$l10n->t('The user id is %d', $this->session->getLocalUserId()))
				->forUser($admin)
				->withRecipient($admin['email'])
				->build();
			$this->emailer->send($email);
		}

		try {
			User::remove($userId);

			$this->session->clear();
			$this->cookie->clear();

			$this->systemMessages->addInfo($this->t('Your account has been successfully removed. Bye bye!'));
			$this->baseUrl->redirect();
		} catch (\RuntimeException $e) {
			$this->systemMessages->addNotice($e->getMessage());
		} finally {
			return;
		}
	}

	protected function content(array $request = []): string
	{
		parent::content();

		if (!$this->session->getLocalUserId()) {
			$this->systemMessages->addNotice($this->t('Permission denied.'));
			$this->baseUrl->redirect();
		}

		$hash = Strings::getRandomHex();

		$this->session->set('remove_account_verify', $hash);

		$tpl = Renderer::getMarkupTemplate('settings/removeme.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'title' => DI::l10n()->t('Remove My Account'),
				'desc'  => DI::l10n()->t('This will completely remove your account. Once this has been done it is not recoverable.'),
			],

			'$hovercard' => Widget\Hovercard::getHTML(User::getOwnerDataById($this->session->getLocalUserId())),

			'$password' => [$hash, $this->t('Please enter your password for verification:'), null, null, true],
		]);
	}
}
