<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\ActivityPub;

use Friendica\Model\User;
use Friendica\Module\BaseApi;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Network;

/**
 * ActivityPub Outbox
 */
class Outbox extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		if (empty($this->parameters['nickname'])) {
			$this->earlyJsonExit([], 'application/activity+json');
		}

		$owner = User::getOwnerDataByNick($this->parameters['nickname']);
		if (empty($owner)) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$uid  = self::getCurrentUserID();
		$page = $request['page'] ?? null;

		if (empty($page) && empty($request['max_id']) && !empty($uid)) {
			$page = 1;
		}

		$outbox = ActivityPub\ClientToServer::getOutbox($owner, $uid, $page, !empty($request['max_id']) ? (int) $request['max_id'] : null, HTTPSignature::getSigner('', $_SERVER));

		$this->earlyJsonExit($outbox, 'application/activity+json');
	}

	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid      = self::getCurrentUserID();
		$postdata = Network::postdata();

		if (empty($postdata) || empty($this->parameters['nickname'])) {
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}

		$owner = User::getOwnerDataByNick($this->parameters['nickname']);
		if (empty($owner)) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}
		if ($owner['uid'] != $uid) {
			throw new \Friendica\Network\HTTPException\ForbiddenException();
		}

		$activity = json_decode($postdata, true);
		if (empty($activity)) {
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}

		$this->earlyJsonExit(ActivityPub\ClientToServer::processActivity($activity, $uid, self::getCurrentApplication() ?? []));
	}
}
