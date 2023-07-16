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

namespace Friendica\Module\Settings\Server;

use Friendica\App;
use Friendica\Content\Pager;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\User\Settings\Entity\UserGServer;
use Friendica\User\Settings\Repository;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Index extends BaseSettings
{
	/** @var Repository\UserGServer */
	private $repository;
	/** @var SystemMessages */
	private $systemMessages;

	public function __construct(SystemMessages $systemMessages, Repository\UserGServer $repository, IHandleUserSessions $session, App\Page $page, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->repository     = $repository;
		$this->systemMessages = $systemMessages;
	}

	protected function post(array $request = [])
	{
		self::checkFormSecurityTokenRedirectOnError($this->args->getQueryString(), 'settings-server');

		foreach ($request['delete'] ?? [] as $gsid => $delete) {
			if ($delete) {
				unset($request['ignored'][$gsid]);

				try {
					$userGServer = $this->repository->selectOneByUserAndServer($this->session->getLocalUserId(), $gsid, false);
					$this->repository->delete($userGServer);
				} catch (NotFoundException $e) {
					// Nothing to delete
				}
			}
		}

		foreach ($request['ignored'] ?? [] as $gsid => $ignored) {
			$userGServer = $this->repository->getOneByUserAndServer($this->session->getLocalUserId(), $gsid, false);
			if ($userGServer->ignored != $ignored) {
				$userGServer->toggleIgnored();
				$this->repository->save($userGServer);
			}
		}

		$this->systemMessages->addInfo($this->t('Settings saved'));

		$this->baseUrl->redirect($this->args->getQueryString());
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$pager = new Pager($this->l10n, $this->args->getQueryString(), 30);

		$total = $this->repository->countByUser($this->session->getLocalUserId());

		$servers = $this->repository->selectByUserWithPagination($this->session->getLocalUserId(), $pager);

		$ignoredCheckboxes = array_map(function (UserGServer $server) {
			return ['ignored[' . $server->gsid . ']', '', $server->ignored];
		}, $servers->getArrayCopy());

		$deleteCheckboxes = array_map(function (UserGServer $server) {
			return ['delete[' . $server->gsid . ']'];
		}, $servers->getArrayCopy());

		$tpl = Renderer::getMarkupTemplate('settings/server/index.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'title'         => $this->t('Remote server settings'),
				'desc'          => $this->t('Here you can find all the remote servers you have taken individual moderation actions against. For a list of servers your node has blocked, please check out the <a href="friendica">Information</a> page.'),
				'siteName'      => $this->t('Server Name'),
				'ignored'       => $this->t('Ignored'),
				'ignored_title' => $this->t("You won't see any content from this server including reshares in your Network page, the community pages and individual conversations."),
				'delete'        => $this->t('Delete'),
				'delete_title'  => $this->t('Delete all your settings for the remote server'),
				'submit'        => $this->t('Save changes'),
			],

			'$count' => $total,

			'$servers' => $servers,

			'$form_security_token' => self::getFormSecurityToken('settings-server'),

			'$ignoredCheckboxes' => $ignoredCheckboxes,
			'$deleteCheckboxes'  => $deleteCheckboxes,

			'$paginate' => $pager->renderFull($total),
		]);
	}
}
