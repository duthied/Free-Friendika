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
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;

/**
 * Dummy class for all currently unimplemented endpoints
 */
class Token extends BaseApi
{
	public static function post(array $parameters = [])
	{
		$client_secret = !isset($_REQUEST['client_secret']) ? '' : $_REQUEST['client_secret'];
		$code          = !isset($_REQUEST['code']) ? '' : $_REQUEST['code'];
		$grant_type    = !isset($_REQUEST['grant_type']) ? '' : $_REQUEST['grant_type'];

		if ($grant_type != 'authorization_code') {
			Logger::warning('Unsupported or missing grant type', ['request' => $_REQUEST]);
			DI::mstdnError()->UnprocessableEntity(DI::l10n()->t('Unsupported or missing grant type'));
		}

		$application = self::getApplication();
		if (empty($application)) {
			DI::mstdnError()->UnprocessableEntity();
		}

		if ($application['client_secret'] != $client_secret) {
			Logger::warning('Wrong client secret', $client_secret);
			DI::mstdnError()->Unauthorized();
		}

		$condition = ['application-id' => $application['id'], 'code' => $code];

		$token = DBA::selectFirst('application-token', ['access_token', 'created_at'], $condition);
		if (!DBA::isResult($token)) {
			Logger::warning('Token not found', $condition);
			DI::mstdnError()->Unauthorized();
		}

		// @todo Use entity class
		System::jsonExit(['access_token' => $token['access_token'], 'token_type' => 'Bearer', 'scope' => $application['scopes'], 'created_at' => $token['created_at']]);
	}
}
