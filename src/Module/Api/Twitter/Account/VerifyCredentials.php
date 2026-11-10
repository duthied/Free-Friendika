<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\Account;

use Friendica\Module\BaseApi;
use Friendica\DI;

/**
 * Returns an HTTP 200 OK response code and a representation of the requesting user if authentication was successful;
 * returns a 401 status code and an error message if not.
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/get-account-verify_credentials
 */
class VerifyCredentials extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		$skip_status = $this->getRequestValue($request, 'skip_status', false);

		$user_info = DI::twitterUser()->createFromUserId($uid, $skip_status)->toArray();

		// "verified" isn't used here in the standard
		unset($user_info["verified"]);

		// "uid" is only needed for some internal stuff, so remove it from here
		unset($user_info['uid']);

		$this->response->addFormattedContent('user', ['user' => $user_info], $this->parameters['extension'] ?? null);
	}
}
