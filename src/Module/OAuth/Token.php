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
 * @see https://docs.joinmastodon.org/spec/oauth/
 * @see https://aaronparecki.com/oauth-2-simplified/
 */
class Token extends BaseApi
{
	public static function post(array $parameters = [])
	{
		$grant_type    = $_REQUEST['grant_type'] ?? '';
		$code          = $_REQUEST['code'] ?? '';
		$redirect_uri  = $_REQUEST['redirect_uri'] ?? '';
		$client_id     = $_REQUEST['client_id'] ?? '';
		$client_secret = $_REQUEST['client_secret'] ?? '';

		// AndStatus transmits the client data in the AUTHORIZATION header field, see https://github.com/andstatus/andstatus/issues/530
		if (empty($client_id) && !empty($_SERVER['HTTP_AUTHORIZATION']) && (substr($_SERVER['HTTP_AUTHORIZATION'], 0, 6) == 'Basic ')) {
			$datapair = explode(':', base64_decode(trim(substr($_SERVER['HTTP_AUTHORIZATION'], 6))));
			if (count($datapair) == 2) {
				$client_id     = $datapair[0];
				$client_secret = $datapair[1];
			}
		}

		if (empty($client_id) || empty($client_secret)) {
			Logger::warning('Incomplete request data', ['request' => $_REQUEST]);
			DI::mstdnError()->UnprocessableEntity(DI::l10n()->t('Incomplete request data'));
		}

		$application = self::getApplication($client_id, $client_secret, $redirect_uri);
		if (empty($application)) {
			DI::mstdnError()->UnprocessableEntity();
		}

		if ($grant_type == 'client_credentials') {
			// the "client_credentials" are used as a token for the application itself.
			// see https://aaronparecki.com/oauth-2-simplified/#client-credentials
			$token = self::createTokenForUser($application, 0, '');
		} elseif ($grant_type == 'authorization_code') {
			// For security reasons only allow freshly created tokens
			$condition = ["`redirect_uri` = ? AND `id` = ? AND `code` = ? AND `created_at` > UTC_TIMESTAMP() - INTERVAL ? MINUTE",
				$redirect_uri, $application['id'], $code, 5];

			$token = DBA::selectFirst('application-view', ['access_token', 'created_at'], $condition);
			if (!DBA::isResult($token)) {
				Logger::warning('Token not found or outdated', $condition);
				DI::mstdnError()->Unauthorized();
			}
		} else {
			Logger::warning('Unsupported or missing grant type', ['request' => $_REQUEST]);
			DI::mstdnError()->UnprocessableEntity(DI::l10n()->t('Unsupported or missing grant type'));
		}

		$object = new \Friendica\Object\Api\Mastodon\Token($token['access_token'], 'Bearer', $application['scopes'], $token['created_at']);

		System::jsonExit($object->toArray());
	}
}
