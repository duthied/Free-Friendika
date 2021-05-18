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

namespace Friendica\Module\Api\Mastodon\Accounts;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/
 */
class Followers extends BaseApi
{
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
		if (!DBA::exists('contact', ['id' => $id, 'uid' => 0])) {
			DI::mstdnError()->RecordNotFound();
		}

		$request = self::getRequest([
			'max_id'   => 0,  // Return results older than this id
			'since_id' => 0,  // Return results newer than this id
			'limit'    => 20, // Maximum number of results to return. Defaults to 20.
		]);

		$params = ['order' => ['cid' => true], 'limit' => $request['limit']];

		$condition = ['relation-cid' => $id, 'follows' => true];

		if (!empty($request['max_id'])) {
			$condition = DBA::mergeConditions($condition, ["`cid` < ?", $request['max_id']]);
		}

		if (!empty($request['since_id'])) {
			$condition = DBA::mergeConditions($condition, ["`cid` > ?", $request['since_id']]);
		}

		if (!empty($min_id)) {
			$condition = DBA::mergeConditions($condition, ["`cid` > ?", $min_id]);

			$params['order'] = ['cid'];
		}

		$followers = DBA::select('contact-relation', ['cid'], $condition, $parameters);
		while ($follower = DBA::fetch($followers)) {
			$accounts[] = DI::mstdnAccount()->createFromContactId($follower['cid'], $uid);
		}
		DBA::close($followers);

		if (!empty($min_id)) {
			array_reverse($accounts);
		}

		System::jsonExit($accounts);
	}
}
