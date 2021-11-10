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

namespace Friendica\Module\Api\Friendica;

use Friendica\Model\Item;
use Friendica\Module\BaseApi;

/**
 * API endpoints:
 * - /api/friendica/activity/like
 * - /api/friendica/activity/dislike
 * - /api/friendica/activity/attendyes
 * - /api/friendica/activity/attendno
 * - /api/friendica/activity/attendmaybe
 * - /api/friendica/activity/unlike
 * - /api/friendica/activity/undislike
 * - /api/friendica/activity/unattendyes
 * - /api/friendica/activity/unattendno
 * - /api/friendica/activity/unattendmaybe
 */
class Activity extends BaseApi
{
	public static function rawContent(array $parameters = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$request = self::getRequest([
			'id' => 0, // Id of the post
		]);

		$res = Item::performActivity($request['id'], $parameters['verb'], $uid);

		if ($res) {
			if (!empty($parameters['extension']) && ($parameters['extension'] == 'xml')) {
				$ok = 'true';
			} else {
				$ok = 'ok';
			}
			self::exit('ok', ['ok' => $ok], $parameters['extension'] ?? null);
		} else {
			self::error(500, 'Error adding activity', '', $parameters['extension'] ?? null);
		}
	}
}
