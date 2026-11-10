<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\Users;

use Friendica\Module\BaseApi;
use Friendica\DI;

/**
 * Returns extended information of a given user, specified by ID or screen name as per the required id parameter.
 * The author's most recent status will be returned inline.
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-users-show
 */
class Show extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$cid = BaseApi::getContactIDForSearchterm($this->getRequestValue($request, 'screen_name', ''), $this->getRequestValue($request, 'profileurl', ''), $this->getRequestValue($request, 'user_id', 0), $uid);
		} else {
			$cid = (int) $this->parameters['id'];
		}

		$user_info = DI::twitterUser()->createFromContactId($cid, $uid)->toArray();

		// "uid" is only needed for some internal stuff, so remove it from here
		unset($user_info['uid']);

		$this->response->addFormattedContent('user', ['user' => $user_info], $this->parameters['extension'] ?? null);
	}
}
