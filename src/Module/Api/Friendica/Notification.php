<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica;

use Friendica\Collection\Api\Notifications as ApiNotifications;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Object\Api\Friendica\Notification as ApiNotification;

/**
 * API endpoint: /api/friendica/notification
 */
class Notification extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$Notifies = DI::notify()->selectAllForUser($uid, 50);

		$notifications = new ApiNotifications();
		foreach ($Notifies as $Notify) {
			$notifications[] = new ApiNotification($Notify);
		}

		if (($this->parameters['extension'] ?? '') == 'xml') {
			$xmlnotes = [];
			foreach ($notifications as $notification) {
				$xmlnotes[] = ['@attributes' => $notification->toArray()];
			}

			$result = $xmlnotes;
		} elseif (count($notifications) > 0) {
			$result = $notifications->getArrayCopy();
		} else {
			$result = false;
		}

		$this->response->addFormattedContent('notes', ['note' => $result], $this->parameters['extension'] ?? null);
	}
}
