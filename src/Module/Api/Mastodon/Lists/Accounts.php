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

namespace Friendica\Module\Api\Mastodon\Lists;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/lists/
 *
 * Currently the output will be unordered since we use public contact ids in the api and not user contact ids.
 */
class Accounts extends BaseApi
{
	public static function delete(array $parameters = [])
	{
		self::unsupported('delete');
	}

	public static function post(array $parameters = [])
	{
		self::unsupported('post');
	}

	/**
	 * @param array $parameters
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function rawContent(array $parameters = [])
	{
		self::login(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (empty($parameters['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		$id = $parameters['id'];
		if (!DBA::exists('group', ['id' => $id, 'uid' => $uid])) {
			DI::mstdnError()->RecordNotFound();
		}

		// Return results older than this id
		$max_id = (int)!isset($_REQUEST['max_id']) ? 0 : $_REQUEST['max_id'];
		// Return results newer than this id
		$since_id = (int)!isset($_REQUEST['since_id']) ? 0 : $_REQUEST['since_id'];
		// Maximum number of results. Defaults to 40. Max 40.
		// Set to 0 in order to get all accounts without pagination.
		$limit = (int)!isset($_REQUEST['limit']) ? 40 : $_REQUEST['limit'];


		$params = ['order' => ['contact-id' => true]];

		if ($limit != 0) {
			$params['limit'] = $limit;

		}
	
		$condition = ['gid' => $id];

		if (!empty($max_id)) {
			$condition = DBA::mergeConditions($condition, ["`contact-id` < ?", $max_id]);
		}

		if (!empty($since_id)) {
			$condition = DBA::mergeConditions($condition, ["`contact-id` > ?", $since_id]);
		}

		if (!empty($min_id)) {
			$condition = DBA::mergeConditions($condition, ["`contact-id` > ?", $min_id]);

			$params['order'] = ['contact-id'];
		}

		$accounts = [];

		$members = DBA::select('group_member', ['contact-id'], $condition, $params);
		while ($member = DBA::fetch($members)) {
			$accounts[] = DI::mstdnAccount()->createFromContactId($member['contact-id'], $uid);
		}
		DBA::close($members);

		if (!empty($min_id)) {
			array_reverse($accounts);
		}

		System::jsonExit($accounts);
	}
}
