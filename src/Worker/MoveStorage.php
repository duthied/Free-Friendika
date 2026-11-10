<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Worker;
use Friendica\DI;

/**
 * Moves up to 5000 attachments and photos to the current storage system.
 * Self-replicates if legacy items have been found and moved.
 *
 */
class MoveStorage
{
	public static function execute()
	{
		$current = DI::storage();
		$moved   = DI::storageManager()->move($current);

		if ($moved) {
			Worker::add(Worker::PRIORITY_LOW, 'MoveStorage');
		}
	}
}
