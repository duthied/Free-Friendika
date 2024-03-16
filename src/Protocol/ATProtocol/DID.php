<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Protocol\ATProtocol;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Network\HTTPException;

/**
 * This class handles DID related activities from the AT Protocol
 */
class DID
{
	/**
	 * Routes AT Protocol DID requests
	 *
	 * @param string $path
	 * @param array $server
	 * @return void
	 */
	public static function routeRequest(string $path, array $server)
	{
		$host = DI::baseUrl()->getHost();

		if (($host == $server['SERVER_NAME']) || !strpos($server['SERVER_NAME'], '.' . $host)) {
			return;
		}

		if (!DI::config()->get('bluesky', 'friendica_handles')) {
			throw new HTTPException\NotFoundException();
		}

		if (!in_array($path, ['.well-known/atproto-did', ''])) {
			throw new HTTPException\NotFoundException();
		}

		$nick = str_replace('.' . $host, '', $server['SERVER_NAME']);

		$user = DBA::selectFirst('user', ['uid'], ['nickname' => $nick, 'verified' => true, 'blocked' => false, 'account_removed' => false, 'account_expired' => false]);
		if (empty($user['uid'])) {
			throw new HTTPException\NotFoundException();
		}

		if (!DI::pConfig()->get($user['uid'], 'bluesky', 'friendica_handle')) {
			throw new HTTPException\NotFoundException();
		}

		if ($path == '') {
			System::externalRedirect(DI::baseUrl() . '/profile/' . urlencode($nick), 0);
		}

		$did = DI::pConfig()->get($user['uid'], 'bluesky', 'did');
		if (empty($did)) {
			throw new HTTPException\NotFoundException();
		}

		header('Content-Type: text/plain');
		echo $did;
		System::exit();
	}
}
