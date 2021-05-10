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
use Friendica\Model\Contact;
use Friendica\Model\Notification;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/mutes/
 */
class Notifications extends BaseApi
{
	/**
	 * @param array $parameters
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function rawContent(array $parameters = [])
	{
		self::login();
		$uid = self::getCurrentUserID();

		if (!empty($parameters['id'])) {
			$id = $parameters['id'];
			if (!DBA::exists('notify', ['id' => $id, 'uid' => $uid])) {
				DI::mstdnError()->RecordNotFound();
			}
			System::jsonExit(DI::mstdnNotification()->createFromNotifyId($id));
		}

		// Return results older than this ID
		$max_id = (int)!isset($_REQUEST['max_id']) ? 0 : $_REQUEST['max_id'];

		// Return results newer than this ID
		$since_id = (int)!isset($_REQUEST['since_id']) ? 0 : $_REQUEST['since_id'];

		// Return results immediately newer than this ID
		$min_id = (int)!isset($_REQUEST['min_id']) ? 0 : $_REQUEST['min_id'];

		// Maximum number of results to return (default 20)
		$limit = (int)!isset($_REQUEST['limit']) ? 20 : $_REQUEST['limit'];

		// Array of types to exclude (follow, favourite, reblog, mention, poll, follow_request)
		$exclude_types = !isset($_REQUEST['exclude_types']) ? [] : $_REQUEST['exclude_types'];

		// Return only notifications received from this account
		$account_id = (int)!isset($_REQUEST['account_id']) ? 0 : $_REQUEST['account_id'];

		$params = ['order' => ['id' => true], 'limit' => $limit];

		$condition = ['uid' => $uid, 'seen' => false, 'type' => []];

		if (!empty($account_id)) {
			$contact = Contact::getById($account_id, ['url']);
			if (!empty($contact['url'])) {
				$condition['url'] = $contact['url'];
			}
		}

		if (!in_array('follow_request', $exclude_types)) {
			$condition['type'] = array_merge($condition['type'], [Notification\Type::INTRO]);
		}

		if (!in_array('mention', $exclude_types)) {
			$condition['type'] = array_merge($condition['type'],
				[Notification\Type::WALL, Notification\Type::COMMENT, Notification\Type::MAIL, Notification\Type::TAG_SELF, Notification\Type::POKE]);
		}

		if (!in_array('status', $exclude_types)) {
			$condition['type'] = array_merge($condition['type'], [Notification\Type::SHARE]);
		}

		if (!empty($max_id)) {
			$condition = DBA::mergeConditions($condition, ["`id` < ?", $max_id]);
		}

		if (!empty($since_id)) {
			$condition = DBA::mergeConditions($condition, ["`id` > ?", $since_id]);
		}

		if (!empty($min_id)) {
			$condition = DBA::mergeConditions($condition, ["`id` > ?", $min_id]);

			$params['order'] = ['id'];
		}

		$notifications = [];

		$notify = DBA::select('notify', ['id'], $condition, $params);
		while ($notification = DBA::fetch($notify)) {
			$notifications[] = DI::mstdnNotification()->createFromNotifyId($notification['id']);
		}

		if (!empty($min_id)) {
			array_reverse($notifications);
		}

		System::jsonExit($notifications);
	}
}
