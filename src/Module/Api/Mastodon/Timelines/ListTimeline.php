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

namespace Friendica\Module\Api\Mastodon\Timelines;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/
 */
class ListTimeline extends BaseApi
{
	/**
	 * @param array $parameters
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function rawContent(array $parameters = [])
	{
		self::login();
		$uid = self::getCurrentUserID();

		if (empty($parameters['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		// Return results older than id
		$max_id = (int)!isset($_REQUEST['max_id']) ? 0 : $_REQUEST['max_id'];
		// Return results newer than id
		$since_id = (int)!isset($_REQUEST['since_id']) ? 0 : $_REQUEST['since_id'];
		// Return results immediately newer than id
		$min_id = (int)!isset($_REQUEST['min_id']) ? 0 : $_REQUEST['min_id'];
		// Maximum number of results to return. Defaults to 20.
		$limit = (int)!isset($_REQUEST['limit']) ? 20 : $_REQUEST['limit'];

		$params = ['order' => ['uri-id' => true], 'limit' => $limit];

		$condition = ["`uid` = ? AND `gravity` IN (?, ?) AND `contact-id` IN (SELECT `contact-id` FROM `group_member` WHERE `gid` = ?)",
			$uid, GRAVITY_PARENT, GRAVITY_COMMENT, $parameters['id']];

		if (!empty($max_id)) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` < ?", $max_id]);
		}

		if (!empty($since_id)) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", $since_id]);
		}

		if (!empty($min_id)) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", $min_id]);

			$params['order'] = ['uri-id'];
		}

		$items = Post::selectForUser($uid, ['uri-id'], $condition, $params);

		$statuses = [];
		while ($item = Post::fetch($items)) {
			$statuses[] = DI::mstdnStatus()->createFromUriId($item['uri-id'], $uid);
		}
		DBA::close($items);

		if (!empty($min_id)) {
			array_reverse($statuses);
		}

		System::jsonExit($statuses);
	}
}
