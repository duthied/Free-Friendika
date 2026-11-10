<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica\Circle;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * API endpoint: /api/friendica/circle_show
 * API endpoint: /api/friendica/group_show
 */
class Show extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_READ);
		$uid  = BaseApi::getCurrentUserID();
		$type = $this->getRequestValue($this->parameters, 'extension', 'json');

		// params
		$gid = $this->getRequestValue($request, 'gid', 0);

		// get data of the specified circle id or all circles if not specified
		if ($gid != 0) {
			$circles = DBA::selectToArray('group', [], ['deleted' => false, 'uid' => $uid, 'id' => $gid]);

			// error message if specified gid is not in database
			if (!DBA::isResult($circles)) {
				throw new HTTPException\BadRequestException('gid not available');
			}
		} else {
			$circles = DBA::selectToArray('group', [], ['deleted' => false, 'uid' => $uid]);
		}

		// loop through all circles and retrieve all members for adding data in the user array
		$grps = [];
		foreach ($circles as $circle) {
			$members = Contact\Circle::getById($circle['id']);
			$users   = [];

			if ($type == 'xml') {
				$user_element = 'users';
				$k            = 0;
				foreach ($members as $member) {
					$users[$k++ . ':user'] = DI::twitterUser()->createFromContactId($member['contact-id'], $uid, true)->toArray();
				}
			} else {
				$user_element = 'user';
				foreach ($members as $member) {
					$users[] = DI::twitterUser()->createFromContactId($member['contact-id'], $uid, true)->toArray();
				}
			}
			$grps[] = ['name' => $circle['name'], 'gid' => $circle['id'], $user_element => $users];
		}

		$this->response->addFormattedContent('group_update', ['group' => $grps], $this->parameters['extension'] ?? null);
	}
}
