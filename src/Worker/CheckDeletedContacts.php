<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Worker;
use Friendica\Database\DBA;

/**
 * Checks for contacts that are about to be deleted and ensures that they are removed.
 * This should be done automatically in the "remove" function. This here is a cleanup job.
 */
class CheckDeletedContacts
{
	public static function execute()
	{
		$contacts = DBA::select('contact', ['id'], ['deleted' => true]);
		while ($contact = DBA::fetch($contacts)) {
			Worker::add(Worker::PRIORITY_MEDIUM, 'Contact\Remove', $contact['id']);
		}
		DBA::close($contacts);
	}
}
