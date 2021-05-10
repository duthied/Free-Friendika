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
 * @see https://docs.joinmastodon.org/methods/timelines/lists/
 */
class Lists extends BaseApi
{
	public static function delete(array $parameters = [])
	{
		self::unsupported('delete');
	}

	public static function post(array $parameters = [])
	{
		self::unsupported('post');
	}

	public static function put(array $parameters = [])
	{
		self::unsupported('put');
	}

	/**
	 * @param array $parameters
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function rawContent(array $parameters = [])
	{
		self::login();
		$uid = self::getCurrentUserID();

		if (empty($parameters['id'])) {
			$lists = [];

			$groups = DBA::select('group', ['id'], ['uid' => $uid, 'deleted' => false]);
			while ($group = DBA::fetch($groups)) {
				$lists[] = DI::mstdnList()->createFromGroupId($group['id']);
			}
			DBA::close($groups);
		} else {
			$id = $parameters['id'];
			if (!DBA::exists('group',['uid' => $uid, 'deleted' => false])) {
				DI::mstdnError()->RecordNotFound();
			}
			$lists = DI::mstdnList()->createFromGroupId($id);
		}

		System::jsonExit($lists);
	}
}
