<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Module\Notifications;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Network\HTTPException;

/**
 * Interacting with the /notification command
 */
class Notification extends BaseModule
{
	public static function init(array $parameters = [])
	{
		if (!local_user()) {
			throw new HTTPException\UnauthorizedException(DI::l10n()->t('Permission denied.'));
		}
	}

	public static function post(array $parameters = [])
	{
		$request_id = $parameters['id'] ?? false;

		if ($request_id) {
			$intro = DI::intro()->selectFirst(['id' => $request_id, 'uid' => local_user()]);

			switch ($_POST['submit']) {
				case DI::l10n()->t('Discard'):
					$intro->discard();
					break;
				case DI::l10n()->t('Ignore'):
					$intro->ignore();
					break;
			}

			DI::baseUrl()->redirect('notifications/intros');
		}
	}

	public static function rawContent(array $parameters = [])
	{
		// @TODO: Replace with parameter from router
		if (DI::args()->get(1) === 'mark' && DI::args()->get(2) === 'all') {
			try {
				$success = DI::notify()->setSeen();
			} catch (\Exception $e) {
				DI::logger()->warning('set all seen failed.', ['exception' => $e]);
				$success = false;
			}

			System::jsonExit(['result' => (($success) ? 'success' : 'fail')]);
		}
	}

	/**
	 * Redirect to the notifications main page or to the url for the chosen notifications
	 *
	 * @return string|void
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function content(array $parameters = [])
	{
		$request_id = $parameters['id'] ?? false;

		if ($request_id) {
			try {
				$notify = DI::notify()->getByID($request_id);
				DI::notify()->setSeen(true, $notify);

				if (!empty($notify->link)) {
					System::externalRedirect($notify->link);
				}

			} catch (HTTPException\NotFoundException $e) {
				info(DI::l10n()->t('Invalid notification.'));
			}

			DI::baseUrl()->redirect();
		}

		DI::baseUrl()->redirect('notifications/system');
	}
}
