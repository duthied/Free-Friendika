<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Module\Api\Mastodon\Statuses;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/statuses/
 */
class Unbookmark extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$item = Post::selectOriginal(['uid', 'id', 'uri-id', 'gravity'], ['uri-id' => $this->parameters['id'], 'uid' => [$uid, 0]], ['order' => ['uid' => true]]);
		if (!DBA::isResult($item)) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		if ($item['gravity'] != Item::GRAVITY_PARENT) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity($this->t('Only starting posts can be unbookmarked')));
		}

		if ($item['uid'] == 0) {
			$stored = Item::storeForUserByUriId($item['uri-id'], $uid, ['post-reason' => Item::PR_ACTIVITY]);
			if (!empty($stored)) {
				$item = Post::selectFirst(['id', 'gravity'], ['id' => $stored]);
				if (!DBA::isResult($item)) {
					$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
				}
			} else {
				$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
			}
		}

		Item::update(['starred' => false], ['id' => $item['id']]);

		// @TODO Remove once mstdnStatus()->createFromUriId is fixed so that it returns posts not reshared posts if given an ID to an original post that has been reshared
		// Introduced in this PR: https://github.com/friendica/friendica/pull/13175
		// Issue tracking the behavior of createFromUriId: https://github.com/friendica/friendica/issues/13350
		$isReblog = $item['uri-id'] != $this->parameters['id'];

		$this->jsonExit(DI::mstdnStatus()->createFromUriId($this->parameters['id'], $uid, self::appSupportsQuotes(), $isReblog)->toArray());
	}
}
