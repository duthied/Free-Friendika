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

namespace Friendica\Module\Calendar;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Event;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Controller to export a calendar from a given user
 */
class Export extends BaseModule
{
	const EXPORT_ICAL = 'ical';
	const EXPORT_CSV  = 'csv';

	const DEFAULT_EXPORT = self::EXPORT_ICAL;

	/** @var IHandleUserSessions */
	protected $session;
	/** @var SystemMessages */
	protected $sysMessages;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $session, SystemMessages $sysMessages, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session     = $session;
		$this->sysMessages = $sysMessages;
	}

	protected function rawContent(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			throw new HTTPException\UnauthorizedException($this->t('Permission denied.'));
		}

		$owner = User::getByNickname($this->parameters['nickname'], ['uid']);
		if (empty($owner)) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}
		$ownerUid = $owner['uid'];
		$format   = $this->parameters['format'] ?: static::DEFAULT_EXPORT;

		// Get the export data by uid
		$evexport = Event::exportListByUserId($ownerUid, $format);

		if (!$evexport["success"]) {
			if ($evexport["content"]) {
				$this->sysMessages->addNotice($this->t('This calendar format is not supported'));
			} else {
				$this->sysMessages->addNotice($this->t('No exportable data found'));
			}

			// If it is the own calendar return to the events page
			// otherwise to the profile calendar page
			if ($this->session->getLocalUserId() === $ownerUid) {
				$returnPath = 'calendar';
			} else {
				$returnPath = 'calendar/show/' . $this->parameters['nickname'];
			}

			$this->baseUrl->redirect($returnPath);
		}

		// If nothing went wrong we can echo the export content
		if ($evexport["success"]) {
			$this->response->setHeader(sprintf('Content-Disposition: attachment; filename="%s-%s.%s"',
				$this->t('calendar'),
				$this->parameters['nickname'],
				$evexport["extension"]
			));

			switch ($format) {
				case static::EXPORT_ICAL:
					$this->response->setType(Response::TYPE_BLANK, 'text/ics');
					break;
				case static::EXPORT_CSV:
					$this->response->setType(Response::TYPE_BLANK, 'text/csv');
					break;
			}

			$this->response->addContent($evexport['content']);
		}
	}
}
