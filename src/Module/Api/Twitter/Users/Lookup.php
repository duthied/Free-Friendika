<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\Users;

use Friendica\Module\BaseApi;
use Friendica\DI;
use Friendica\Network\HTTPException\NotFoundException;

/**
 * Return user objects
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-users-lookup
 */
class Lookup extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		$users = [];

		if (!empty($request['user_id'])) {
			foreach (explode(',', $request['user_id']) as $cid) {
				if (!empty($cid) && is_numeric($cid)) {
					$users[] = DI::twitterUser()->createFromContactId((int) $cid, $uid, false)->toArray();
				}
			}
		}

		if (empty($users)) {
			throw new NotFoundException();
		}

		$this->response->addFormattedContent('users', ['user' => $users], $this->parameters['extension'] ?? null);
	}
}
