<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Module\User;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Contact\Introduction\Repository\Introduction;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Model\Notification;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Navigation\Notifications\Repository\Notify;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Security\Authentication;
use Friendica\Util;
use Psr\Log\LoggerInterface;

/**
 * Switches current user between delegates/parent user
 */
class Delegation extends BaseModule
{
	/** @var IHandleUserSessions */
	private $session;
	/** @var Database */
	private $db;
	/** @var Authentication */
	private $auth;
	/** @var SystemMessages */
	private $systemMessages;
	/** @var Notify */
	private $notify;
	/** @var Introduction */
	private $intro;
	/** @var App */
	private $app;

	public function __construct(App $app, Introduction $intro, Notify $notify, SystemMessages $systemMessages, Authentication $auth, Database $db, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Util\Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session        = $session;
		$this->db             = $db;
		$this->auth           = $auth;
		$this->systemMessages = $systemMessages;
		$this->notify         = $notify;
		$this->intro          = $intro;
		$this->app            = $app;
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			return;
		}

		$uid         = $this->session->getLocalUserId();
		$orig_record = User::getById($this->session->getLocalUserId());

		if ($this->session->getSubManagedUserId()) {
			$user = User::getById($this->session->getSubManagedUserId());
			if ($this->db->isResult($user)) {
				$uid         = intval($user['uid']);
				$orig_record = $user;
			}
		}

		$identity = intval($request['identity'] ?? 0);
		if (!$identity) {
			return;
		}

		$limited_id  = 0;
		$original_id = $uid;

		$manages = $this->db->selectToArray('manage', ['mid'], ['uid' => $uid]);
		foreach ($manages as $manage) {
			if ($identity == $manage['mid']) {
				$limited_id = $manage['mid'];
				break;
			}
		}

		if ($limited_id) {
			$user = User::getById($limited_id);
		} else {
			// Check if the target user is one of our children
			$user = $this->db->selectFirst('user', [], ['uid' => $identity, 'parent-uid' => $orig_record['uid']]);

			// Check if the target user is one of our siblings
			if (!$this->db->isResult($user) && $orig_record['parent-uid']) {
				$user = $this->db->selectFirst('user', [], ['uid' => $identity, 'parent-uid' => $orig_record['parent-uid']]);
			}

			// Check if it's our parent or our own user
			if (!$this->db->isResult($user)
				&& (
					$orig_record['parent-uid'] && $orig_record['parent-uid'] === $identity
					||
					$orig_record['uid'] && $orig_record['uid'] === $identity
				)
			) {
				$user = User::getById($identity);
			}
		}

		if (!$this->db->isResult($user)) {
			return;
		}

		$this->session->clear();

		$this->auth->setForUser($this->app, $user, true, true);

		if ($limited_id) {
			$this->session->setSubManagedUserId($original_id);
		}

		$ret = [];
		Hook::callAll('home_init', $ret);

		$this->systemMessages->addNotice($this->t('You are now logged in as %s', $user['username']));

		$this->baseUrl->redirect('network');
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			throw new ForbiddenException($this->t('Permission denied.'));
		}

		$identities = User::identities($this->session->getSubManagedUserId() ?: $this->session->getLocalUserId());

		//getting additional information for each identity
		foreach ($identities as $key => $identity) {
			$identities[$key]['thumb'] = User::getAvatarUrl($identity, Util\Proxy::SIZE_THUMB);

			$identities[$key]['selected'] = ($identity['nickname'] === $this->session->getLocalUserNickname());

			$notifications = $this->notify->countForUser(
				$identity['uid'],
				["`msg` != '' AND NOT (`type` IN (?, ?)) AND NOT `seen`", Notification\Type::INTRO, Notification\Type::MAIL],
				['distinct' => true, 'expression' => 'parent']
			);

			$notifications += $this->db->count(
				'mail',
				['uid'      => $identity['uid'], 'seen' => false],
				['distinct' => true, 'expression' => 'convid']
			);

			$notifications += $this->intro->countActiveForUser($identity['uid']);

			$identities[$key]['notifications'] = $notifications;
		}

		$tpl = Renderer::getMarkupTemplate('delegation.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'title'          => $this->t('Switch between your accounts'),
				'settings_label' => $this->t('Manage your accounts'),
				'desc'           => $this->t('Toggle between different identities or community/group pages which share your account details or which you have been granted "manage" permissions'),
				'choose'         => $this->t('Select an identity to manage: '),
				'submit'         => $this->t('Submit'),
			],

			'$identities' => $identities,
		]);
	}
}
