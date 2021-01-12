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

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * @see https://docs.joinmastodon.org/methods/instance/directory/
 */
class Directory extends BaseApi
{
	/**
	 * @param array $parameters
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @see https://docs.joinmastodon.org/methods/instance/directory/
	 */
	public static function rawContent(array $parameters = [])
	{
		$offset = (int)!isset($_REQUEST['offset']) ? 0 : $_REQUEST['offset'];
		$limit = (int)!isset($_REQUEST['limit']) ? 40 : $_REQUEST['limit'];
		$order = !isset($_REQUEST['order']) ? 'active' : $_REQUEST['order'];
		$local = (bool)!isset($_REQUEST['local']) ? false : ($_REQUEST['local'] == 'true');

		Logger::info('directory', ['offset' => $offset, 'limit' => $limit, 'order' => $order, 'local' => $local]);

		if ($local) {
			$table = 'owner-view';
			$condition = ['net-publish' => true];
		} else {
			$table = 'contact';
			$condition = ['uid' => 0, 'hidden' => false, 'network' => Protocol::FEDERATED];
		}

		$params = ['limit' => [$offset, $limit],
			'order' => [($order == 'active') ? 'last-item' : 'created' => true]];

		$accounts = [];
		$contacts = DBA::select($table, ['id', 'uid'], $condition, $params);
		while ($contact = DBA::fetch($contacts)) {
			$accounts[] = DI::mstdnAccount()->createFromContactId($contact['id'], $contact['uid']);
		}
		DBA::close($contacts);

		System::jsonExit($accounts);
	}
}
