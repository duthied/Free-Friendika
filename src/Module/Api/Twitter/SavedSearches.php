<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter;

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;

/**
 * API endpoint: /api/saved_searches
 * @see https://developer.twitter.com/en/docs/twitter-api/v1/accounts-and-users/manage-account-settings/api-reference/get-saved_searches-list
 */
class SavedSearches extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$terms = DBA::select('search', ['id', 'term'], ['uid' => $uid]);

		$result = [];
		while ($term = DBA::fetch($terms)) {
			$result[] = new \Friendica\Object\Api\Twitter\SavedSearch($term);
		}

		DBA::close($terms);

		$this->response->addFormattedContent('terms', ['terms' => $result], $this->parameters['extension'] ?? null);
	}
}
