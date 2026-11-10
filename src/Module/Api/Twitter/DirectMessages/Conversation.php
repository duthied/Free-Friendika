<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\DirectMessages;

use Friendica\Module\Api\Twitter\DirectMessagesEndpoint;
use Friendica\Module\BaseApi;

/**
 * Returns direct messages with a given URI
 */
class Conversation extends DirectMessagesEndpoint
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		$this->getMessages($request, $uid, ["`parent-uri` = ?", $this->getRequestValue($request, 'uri', '')]);
	}
}
