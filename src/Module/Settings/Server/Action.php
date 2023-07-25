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
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Federation\Repository\GServer;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\User\Settings\Repository\UserGServer;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Action extends \Friendica\BaseModule
{
	/** @var IHandleUserSessions */
	private $session;
	/** @var UserGServer */
	private $repository;
	/** @var GServer */
	private $gserverRepo;

	public function __construct(GServer $gserverRepo, UserGServer $repository, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session     = $session;
		$this->repository  = $repository;
		$this->gserverRepo = $gserverRepo;
	}

	public function content(array $request = []): string
	{
		$GServer = $this->gserverRepo->selectOneById($this->parameters['gsid']);

		switch ($this->parameters['action']) {
			case 'ignore':
				$action = $this->t('Do you want to ignore this server?');
				$desc   = $this->t("You won't see any content from this server including reshares in your Network page, the community pages and individual conversations.");
				break;
			case 'unignore':
				$action = $this->t('Do you want to unignore this server?');
				$desc   = '';
				break;
			default:
				throw new BadRequestException('Unknown user server action ' . $this->parameters['action']);
		}

		$tpl = Renderer::getMarkupTemplate('settings/server/action.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'title'    => $this->t('Remote server settings'),
				'action'   => $action,
				'siteName' => $this->t('Server Name'),
				'siteUrl'  => $this->t('Server URL'),
				'desc'     => $desc,
				'submit'   => $this->t('Submit'),
			],

			'$action' => $this->args->getQueryString(),

			'$GServer' => $GServer,

			'$form_security_token' => self::getFormSecurityToken('settings-server'),
		]);
	}

	public function post(array $request = [])
	{
		if (!empty($request['redirect_url'])) {
			self::checkFormSecurityTokenRedirectOnError($this->args->getQueryString(), 'settings-server');
		}

		$userGServer = $this->repository->getOneByUserAndServer($this->session->getLocalUserId(), $this->parameters['gsid']);

		switch ($this->parameters['action']) {
			case 'ignore':
				$userGServer->ignore();
				break;
			case 'unignore':
				$userGServer->unignore();
				break;
			default:
				throw new BadRequestException('Unknown user server action ' . $this->parameters['action']);
		}

		$this->repository->save($userGServer);

		if (!empty($request['redirect_url'])) {
			$this->baseUrl->redirect($request['redirect_url']);
		}

		System::exit();
	}
}
