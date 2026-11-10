<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\DirectMessages;

use Friendica\Model\Contact;
use Friendica\Module\Api\Twitter\DirectMessagesEndpoint;
use Friendica\Module\BaseApi;

/**
 * Returns the most recent direct messages sent to the user.
 *
 * @see https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/get-messages
 */
class Inbox extends DirectMessagesEndpoint
{
	protected function get(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_READ);
		$uid  = BaseApi::getCurrentUserID();
		$pcid = Contact::getPublicIdByUserId($uid);

		$this->getMessages($request, $uid, ["`author-id` != ?", $pcid]);
	}
}
