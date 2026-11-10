<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica\Circle;

use Friendica\Database\DBA;
use Friendica\Model\Circle;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * API endpoint: /api/friendica/circle_create
 * API endpoint: /api/friendica/group_create
 */
class Create extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();

		// params
		$name  = $this->getRequestValue($request, 'name', '');
		$json  = json_decode($request['json'], true);
		$users = $json['user'];

		// error if no name specified
		if ($name == '') {
			throw new HTTPException\BadRequestException('circle name not specified');
		}

		// error message if specified circle name already exists
		if (DBA::exists('group', ['uid' => $uid, 'name' => $name, 'deleted' => false])) {
			throw new HTTPException\BadRequestException('circle name already exists');
		}

		// Check if the circle needs to be reactivated
		if (DBA::exists('group', ['uid' => $uid, 'name' => $name, 'deleted' => true])) {
			$reactivate_circle = true;
		}

		$ret = Circle::create($uid, $name);
		if ($ret) {
			$gid = Circle::getIdByName($uid, $name);
		} else {
			throw new HTTPException\BadRequestException('other API error');
		}

		// add members
		$erroraddinguser = false;
		$errorusers      = [];
		foreach ($users as $user) {
			$cid = $user['cid'];
			if (DBA::exists('contact', ['id' => $cid, 'uid' => $uid])) {
				Circle::addMember($gid, $cid);
			} else {
				$erroraddinguser = true;
				$errorusers[]    = $cid;
			}
		}

		// return success message incl. missing users in array
		$status = ($erroraddinguser ? 'missing user' : (!empty($reactivate_circle) ? 'reactivated' : 'ok'));

		$result = ['success' => true, 'gid' => $gid, 'name' => $name, 'status' => $status, 'wrong users' => $errorusers];

		$this->response->addFormattedContent('group_create', ['$result' => $result], $this->parameters['extension'] ?? null);
	}
}
