<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Module\Api\GNUSocial\Statusnet;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\Module\BaseApi;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Network\HTTPException\BadRequestException;

/**
 * Returns a conversation
 */
class Conversation extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		// params
		$id               = $this->getRequestValue($this->parameters, 'id', 0);
		$since_id         = $this->getRequestValue($request, 'since_id', 0, 0);
		$max_id           = $this->getRequestValue($request, 'max_id', 0, 0);
		$count            = $this->getRequestValue($request, 'count', 20, 1, 100);
		$page             = $this->getRequestValue($request, 'page', 1, 1);
		$include_entities = $this->getRequestValue($request, 'include_entities', false);

		$start = max(0, ($page - 1) * $count);

		if ($id == 0) {
			$id = $this->getRequestValue($request, 'id', 0);
		}

		Logger::info(BaseApi::LOG_PREFIX . '{subaction}', ['module' => 'api', 'action' => 'conversation', 'subaction' => 'show', 'id' => $id]);

		// try to fetch the item for the local user - or the public item, if there is no local one
		$item = Post::selectFirst(['parent-uri-id'], ['uri-id' => $id]);
		if (!DBA::isResult($item)) {
			throw new BadRequestException("There is no status with the id $id.");
		}

		$parent = Post::selectFirst(['id'], ['uri-id' => $item['parent-uri-id'], 'uid' => [0, $uid]], ['order' => ['uid' => true]]);
		if (!DBA::isResult($parent)) {
			throw new BadRequestException("There is no status with this id.");
		}

		$id = $parent['id'];

		$condition = ["`parent` = ? AND `uid` IN (0, ?) AND `gravity` IN (?, ?) AND `uri-id` > ?",
			$id, $uid, Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT, $since_id];

		if ($max_id > 0) {
			$condition[0] .= " AND `uri-id` <= ?";
			$condition[] = $max_id;
		}

		$params   = ['order' => ['uri-id' => true], 'limit' => [$start, $count]];
		$statuses = Post::selectForUser($uid, [], $condition, $params);

		if (!DBA::isResult($statuses)) {
			throw new BadRequestException("There is no status with id $id.");
		}

		$ret = [];
		while ($status = DBA::fetch($statuses)) {
			$ret[] = DI::twitterStatus()->createFromUriId($status['uri-id'], $status['uid'], $include_entities)->toArray();
		}
		DBA::close($statuses);

		$this->response->addFormattedContent('statuses', ['status' => $ret], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
