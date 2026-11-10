<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica;

use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;

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
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'id' => 0, // Id of the post
		], $request);

		$post = Post::selectFirst(['id'], ['uri-id' => $request['id'], 'uid' => [0, $uid]], ['order' => ['uid' => true]]);
		if (empty($post['id'])) {
			throw new BadRequestException('Item id not found');
		}

		$res = Item::performActivity($post['id'], $this->parameters['verb'], $uid);

		if ($res) {
			$status_info = DI::twitterStatus()->createFromUriId($request['id'], $uid)->toArray();
			$this->response->addFormattedContent('status', ['status' => $status_info], $this->parameters['extension'] ?? null);
		} else {
			$this->response->error(500, 'Error adding activity', '', $this->parameters['extension'] ?? null);
		}
	}
}
