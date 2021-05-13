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
 * @see https://docs.joinmastodon.org/spec/oauth/
 * @see https://aaronparecki.com/oauth-2-simplified/
 */
class Authorize extends BaseApi
{
	/**
	 * @param array $parameters
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function rawContent(array $parameters = [])
	{
		$response_type = $_REQUEST['response_type'] ?? '';
		$client_id     = $_REQUEST['client_id'] ?? '';
		$client_secret = $_REQUEST['client_secret'] ?? ''; // Isn't normally provided. We will use it if present.
		$redirect_uri  = $_REQUEST['redirect_uri'] ?? '';
		$scope         = $_REQUEST['scope'] ?? '';
		$state         = $_REQUEST['state'] ?? '';

		if ($response_type != 'code') {
			Logger::warning('Unsupported or missing response type', ['request' => $_REQUEST]);
			DI::mstdnError()->UnprocessableEntity(DI::l10n()->t('Unsupported or missing response type'));
		}

		if (empty($client_id) || empty($redirect_uri)) {
			Logger::warning('Incomplete request data', ['request' => $_REQUEST]);
			DI::mstdnError()->UnprocessableEntity(DI::l10n()->t('Incomplete request data'));
		}

		$application = self::getApplication($client_id, $client_secret, $redirect_uri);
		if (empty($application)) {
			DI::mstdnError()->UnprocessableEntity();
		}

		// @todo Compare the application scope and requested scope

		$request = $_REQUEST;
		unset($request['pagename']);
		$redirect = 'oauth/authorize?' . http_build_query($request);

		$uid = local_user();
		if (empty($uid)) {
			Logger::info('Redirect to login');
			DI::app()->redirect('login?return_path=' . urlencode($redirect));
		} else {
			Logger::info('Already logged in user', ['uid' => $uid]);
		}

		if (!self::existsTokenForUser($application, $uid) && !DI::session()->get('oauth_acknowledge')) {
			Logger::info('Redirect to acknowledge');
			DI::app()->redirect('oauth/acknowledge?' . http_build_query(['return_path' => $redirect, 'application' => $application['name']]));
		}

		DI::session()->remove('oauth_acknowledge');

		$token = self::createTokenForUser($application, $uid, $scope);
		if (!$token) {
			DI::mstdnError()->UnprocessableEntity();
		}

		DI::app()->redirect($application['redirect_uri'] . '?' . http_build_query(['code' => $token['code'], 'state' => $state]));
	}
}
