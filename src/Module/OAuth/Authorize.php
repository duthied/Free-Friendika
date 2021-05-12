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

namespace Friendica\Module\OAuth;

use Friendica\Core\Logger;
use Friendica\DI;
use Friendica\Module\BaseApi;

/**
 * Dummy class for all currently unimplemented endpoints
 */
class Authorize extends BaseApi
{
	/**
	 * @param array $parameters
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function rawContent(array $parameters = [])
	{
		$response_type = !isset($_REQUEST['response_type']) ? '' : $_REQUEST['response_type'];
		if ($response_type != 'code') {
			Logger::warning('Wrong or missing response type', ['response_type' => $response_type]);
			DI::mstdnError()->RecordNotFound();
		}

		$application = self::getApplication();
		if (empty($application)) {
			DI::mstdnError()->RecordNotFound();
		}

		$request = $_REQUEST;
		unset($request['pagename']);
		$redirect = urlencode('oauth/authorize?' . http_build_query($request));

		$uid = local_user();
		if (empty($uid)) {
			Logger::info('Redirect to login');
			DI::app()->redirect('login?return_path=' . $redirect);
		} else {
			Logger::info('Already logged in user', ['uid' => $uid]);
		}

		if (!self::existsTokenForUser($application, $uid) && !DI::session()->get('oauth_acknowledge')) {
			Logger::info('Redirect to acknowledge');
			DI::app()->redirect('oauth/acknowledge?return_path=' . $redirect);
		}

		DI::session()->remove('oauth_acknowledge');

		$token = self::createTokenForUser($application, $uid);
		if (!$token) {
			DI::mstdnError()->RecordNotFound();
		}

		DI::app()->redirect($application['redirect_uri'] . '?code=' . $token['code']);
	}
}
