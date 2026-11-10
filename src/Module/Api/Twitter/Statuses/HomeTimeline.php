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
 * Returns the most recent statuses posted by the user and the users they follow.
 *
 * @see https://developer.twitter.com/en/docs/tweets/timelines/api-reference/get-statuses-home_timeline
 */
class HomeTimeline extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		// get last network messages

		// params
		$count            = $this->getRequestValue($request, 'count', 20, 1, 100);
		$page             = $this->getRequestValue($request, 'page', 1, 1);
		$since_id         = $this->getRequestValue($request, 'since_id', 0, 0);
		$max_id           = $this->getRequestValue($request, 'max_id', 0, 0);
		$exclude_replies  = $this->getRequestValue($request, 'exclude_replies', false);
		$include_entities = $this->getRequestValue($request, 'include_entities', false);
		$conversation_id  = $this->getRequestValue($request, 'conversation_id', 0, 0);

		$start = max(0, ($page - 1) * $count);

		$condition = ["`uid` = ? AND `gravity` IN (?, ?) AND `uri-id` > ?",
			$uid, Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT, $since_id];

		if ($max_id > 0) {
			$condition[0] .= " AND `uri-id` <= ?";
			$condition[] = $max_id;
		}
		if ($exclude_replies) {
			$condition[0] .= ' AND `gravity` = ?';
			$condition[] = Item::GRAVITY_PARENT;
		}
		if ($conversation_id > 0) {
			$condition[0] .= " AND `parent-uri-id` = ?";
			$condition[] = $conversation_id;
		}

		$params   = ['order' => ['uri-id' => true], 'limit' => [$start, $count]];
		$statuses = Post::selectForUser($uid, [], $condition, $params);

		$ret     = [];
		$idarray = [];
		while ($status = DBA::fetch($statuses)) {
			$ret[]     = DI::twitterStatus()->createFromUriId($status['uri-id'], $status['uid'], $include_entities)->toArray();
			$idarray[] = intval($status['id']);
		}
		DBA::close($statuses);

		if (!empty($idarray)) {
			$unseen = Post::exists(['unseen' => true, 'id' => $idarray]);
			if ($unseen) {
				Item::update(['unseen' => false], ['unseen' => true, 'id' => $idarray]);
			}
		}

		$this->response->addFormattedContent('statuses', ['status' => $ret], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
