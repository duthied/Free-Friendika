<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker\Contact;

use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Model\Contact;

class Unblock
{
	public const WORKER_DEFER_LIMIT = 5;

	/**
	 * Issue asynchronous unblock message to remote servers.
	 *
	 * @param int $cid Target public contact (uid = 0) id
	 * @param int $uid Source local user id
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function execute(int $cid, int $uid)
	{
		$contact = Contact::getById($cid);
		if (empty($contact)) {
			return;
		}

		$result = Protocol::unblock($contact, $uid);
		if ($result === false) {
			Worker::defer(self::WORKER_DEFER_LIMIT);
		}
	}
}
