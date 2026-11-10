<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\Users;

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\NotFoundException;

/**
 * Search a public user account.
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-users-search
 */
class Search extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		$userlist = [];

		if (!empty($request['q'])) {
			$contacts = Contact::selectToArray(
				['id'],
				[
					'`uid` = 0 AND (`name` = ? OR `nick` = ? OR `url` = ? OR `addr` = ?)',
					$request['q'],
					$request['q'],
					$request['q'],
					$request['q'],
				],
			);

			if (DBA::isResult($contacts)) {
				$k = 0;
				foreach ($contacts as $contact) {
					$user_info = DI::twitterUser()->createFromContactId($contact['id'], $uid, false)->toArray();

					$userlist[] = $user_info;
				}
				$userlist = ['users' => $userlist];
			} else {
				throw new NotFoundException('User ' . $request['q'] . ' not found.');
			}
		} else {
			throw new BadRequestException('No search term specified.');
		}

		$this->response->addFormattedContent('users', $userlist, $this->parameters['extension'] ?? null);
	}
}
