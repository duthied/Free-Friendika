<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\Statuses;

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;

/**
 * Returns the most recent statuses posted by users this node knows about.
 */
class NetworkPublicTimeline extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		$count            = $this->getRequestValue($request, 'count', 20, 1, 100);
		$page             = $this->getRequestValue($request, 'page', 1, 1);
		$since_id         = $this->getRequestValue($request, 'since_id', 0, 0);
		$max_id           = $this->getRequestValue($request, 'max_id', 0, 0);
		$include_entities = $this->getRequestValue($request, 'include_entities', false);

		$start = max(0, ($page - 1) * $count);

		$condition = ["`uid` = 0 AND `gravity` IN (?, ?) AND `uri-id` > ? AND `private` = ?",
			Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT, $since_id, Item::PUBLIC];

		if ($max_id > 0) {
			$condition[0] .= " AND `uri-id` <= ?";
			$condition[] = $max_id;
		}

		$params   = ['order' => ['uri-id' => true], 'limit' => [$start, $count]];
		$statuses = Post::selectForUser($uid, Item::DISPLAY_FIELDLIST, $condition, $params);

		$ret = [];
		while ($status = DBA::fetch($statuses)) {
			$ret[] = DI::twitterStatus()->createFromUriId($status['uri-id'], $status['uid'], $include_entities)->toArray();
		}
		DBA::close($statuses);

		$this->response->addFormattedContent('statuses', ['status' => $ret], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
