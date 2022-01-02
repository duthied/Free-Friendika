<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Module\Api\Twitter\Lists;

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Network\HTTPException\BadRequestException;

/**
 * Returns recent statuses from users in the specified group.
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-ownerships
 */
class Statuses extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		if (empty($_REQUEST['list_id'])) {
			throw new BadRequestException('list_id not specified');
		}

		// params
		$count           = $_REQUEST['count']    ?? 20;
		$page            = $_REQUEST['page']     ?? 1;
		$since_id        = $_REQUEST['since_id'] ?? 0;
		$max_id          = $_REQUEST['max_id']   ?? 0;
		$exclude_replies = (!empty($_REQUEST['exclude_replies']) ? 1 : 0);
		$conversation_id = $_REQUEST['conversation_id'] ?? 0;

		$start = max(0, ($page - 1) * $count);

		$groups    = DBA::selectToArray('group_member', ['contact-id'], ['gid' => 1]);
		$gids      = array_column($groups, 'contact-id');
		$condition = ['uid' => $uid, 'gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT], 'group-id' => $gids];
		$condition = DBA::mergeConditions($condition, ["`id` > ?", $since_id]);

		if ($max_id > 0) {
			$condition[0] .= " AND `id` <= ?";
			$condition[] = $max_id;
		}
		if ($exclude_replies > 0) {
			$condition[0] .= ' AND `gravity` = ?';
			$condition[] = GRAVITY_PARENT;
		}
		if ($conversation_id > 0) {
			$condition[0] .= " AND `parent` = ?";
			$condition[] = $conversation_id;
		}

		$params   = ['order' => ['id' => true], 'limit' => [$start, $count]];
		$statuses = Post::selectForUser($uid, [], $condition, $params);

		$include_entities = strtolower(($_REQUEST['include_entities'] ?? 'false') == 'true');

		$items = [];
		while ($status = DBA::fetch($statuses)) {
			$items[] = DI::twitterStatus()->createFromUriId($status['uri-id'], $status['uid'], $include_entities)->toArray();
		}
		DBA::close($statuses);

		$this->response->exit('statuses', ['status' => $items], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
