<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license   GNU AGPL version 3 or any later version
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

namespace Friendica\Module\Api\Friendica\Events;

use Friendica\Content\Text\BBCode;
use Friendica\Database\DBA;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * api/friendica/events
 *
 * @package Friendica\Module\Api\Friendica\Events
 */
class Index extends BaseApi
{
	public static function rawContent(array $parameters = [])
	{
		if (self::login() === false) {
			throw new HTTPException\ForbiddenException();
		}

		$since_id = $_REQUEST['since_id'] ?? 0;
		$count    = $_REQUEST['count'] ?? 20;

		$condition = ["`id` > ? AND `uid` = ?", $since_id, self::$current_user_id];
		$params = ['limit' => $count];
		$events = DBA::selectToArray('event', [], $condition, $params);

		$items = [];
		foreach ($events as $event) {
			$items[] = [
				'id'        => intval($event['id']),
				'uid'       => intval($event['uid']),
				'cid'       => $event['cid'],
				'uri'       => $event['uri'],
				'name'      => $event['summary'],
				'desc'      => BBCode::convert($event['desc']),
				'startTime' => $event['start'],
				'endTime'   => $event['finish'],
				'type'      => $event['type'],
				'nofinish'  => $event['nofinish'],
				'place'     => $event['location'],
				'adjust'    => $event['adjust'],
				'ignore'    => $event['ignore'],
				'allow_cid' => $event['allow_cid'],
				'allow_gid' => $event['allow_gid'],
				'deny_cid'  => $event['deny_cid'],
				'deny_gid'  => $event['deny_gid']
			];
		}

		echo self::format('events', ['events' => $items]);
		exit;
	}
}
