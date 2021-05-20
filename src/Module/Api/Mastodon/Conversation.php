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

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/conversations/
 */
class Conversation extends BaseApi
{
	public static function delete(array $parameters = [])
	{
		self::login(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		if (!empty($parameters['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		DBA::delete('conv', ['id' => $parameters['id'], 'uid' => $uid]);
		DBA::delete('mail', ['convid' => $parameters['id'], 'uid' => $uid]);

		System::jsonExit([]);
	}

	/**
	 * @param array $parameters
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function rawContent(array $parameters = [])
	{
		self::login(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = self::getRequest([
			'limit'    => 20, // Maximum number of results. Defaults to 20. Max 40.
			'max_id'   => 0,  // Return results older than this ID. Use HTTP Link header to paginate.
			'since_id' => 0,  // Return results newer than this ID. Use HTTP Link header to paginate.
			'min_id'   => 0,  // Return results immediately newer than this ID. Use HTTP Link header to paginate.
		]);

		$params = ['order' => ['id' => true], 'limit' => $request['limit']];

		$condition = ['uid' => $uid];

		if (!empty($request['max_id'])) {
			$condition = DBA::mergeConditions($condition, ["`id` < ?", $request['max_id']]);
		}

		if (!empty($request['since_id'])) {
			$condition = DBA::mergeConditions($condition, ["`id` > ?", $request['since_id']]);
		}

		if (!empty($request['min_id'])) {
			$condition = DBA::mergeConditions($condition, ["`id` > ?", $request['min_id']]);

			$params['order'] = ['id'];
		}

		$convs = DBA::select('conv', ['id'], $condition, $params);

		$conversations = [];

		while ($conv = DBA::fetch($convs)) {
			$conversations[] = DI::mstdnConversation()->CreateFromConvId($conv['id']);
		}

		DBA::close($convs);

		if (!empty($request['min_id'])) {
			array_reverse($conversations);
		}

		System::jsonExit($conversations);
	}
}
