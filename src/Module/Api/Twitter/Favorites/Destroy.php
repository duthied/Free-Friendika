<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\Favorites;

use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;

/**
 * @see https://developer.twitter.com/en/docs/twitter-api/v1/tweets/post-and-engage/api-reference/post-favorites-destroy
 */
class Destroy extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$id = $this->getRequestValue($request, 'id', 0);

		if (empty($id)) {
			throw new BadRequestException('Item id not specified');
		}

		$post = Post::selectFirst(['id'], ['uri-id' => $request['id'], 'uid' => [0, $uid]], ['order' => ['uid' => true]]);
		if (empty($post['id'])) {
			throw new BadRequestException('Item id not found');
		}

		Item::performActivity($post['id'], 'unlike', $uid);

		$status_info = DI::twitterStatus()->createFromUriId($id, $uid)->toArray();

		$this->response->addFormattedContent('status', ['status' => $status_info], $this->parameters['extension'] ?? null);
	}
}
