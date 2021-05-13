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

		if ($grant_type != 'authorization_code') {
			Logger::warning('Unsupported or missing grant type', ['request' => $_REQUEST]);
			DI::mstdnError()->UnprocessableEntity(DI::l10n()->t('Unsupported or missing grant type'));
		}

		if (empty($client_id) || empty($client_secret) || empty($redirect_uri)) {
			Logger::warning('Incomplete request data', ['request' => $_REQUEST]);
			DI::mstdnError()->UnprocessableEntity(DI::l10n()->t('Incomplete request data'));
		}

		$application = self::getApplication($client_id, $client_secret, $redirect_uri);
		if (empty($application)) {
			DI::mstdnError()->UnprocessableEntity();
		}

		// For security reasons only allow freshly created tokens
		$condition = ["`application-id` = ? AND `code` = ? AND `created_at` > UTC_TIMESTAMP() - INTERVAL ? MINUTE", $application['id'], $code, 5];

		$token = DBA::selectFirst('application-token', ['access_token', 'created_at'], $condition);
		if (!DBA::isResult($token)) {
			Logger::warning('Token not found or outdated', $condition);
			DI::mstdnError()->Unauthorized();
		}

		$object = new \Friendica\Object\Api\Mastodon\Token($token['access_token'], 'Bearer', $application['scopes'], $token['created_at']);

		System::jsonExit($object->toArray());
	}
}
