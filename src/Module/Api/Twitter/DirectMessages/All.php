<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\DirectMessages;

use Friendica\Module\Api\Twitter\DirectMessagesEndpoint;
use Friendica\Module\BaseApi;

/**
 * Returns all direct messages
 */
class All extends DirectMessagesEndpoint
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		$this->getMessages($request, $uid, []);
	}
}
