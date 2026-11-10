<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Worker;

use Friendica\Database\DBA;

/**
 * Contains the class for the inter process communication
 */
class IPC
{
	/**
	 * Set the flag if some job is waiting
	 *
	 * @param boolean $jobs Is there a waiting job?
	 * @param int $key Key number
	 * @throws \Exception
	 */
	public static function SetJobState(bool $jobs, int $key = 0)
	{
		$stamp = (float) microtime(true);
		DBA::replace('worker-ipc', ['jobs' => $jobs, 'key' => $key]);
	}

	/**
	 * Delete a key entry
	 *
	 * @param int $key Key number
	 * @throws \Exception
	 */
	public static function DeleteJobState(int $key)
	{
		$stamp = (float) microtime(true);
		DBA::delete('worker-ipc', ['key' => $key]);
	}

	/**
	 * Checks if some worker job waits to be executed
	 *
	 * @param int $key Key number
	 * @return bool
	 * @throws \Exception
	 */
	public static function JobsExists(int $key = 0)
	{
		$row = DBA::selectFirst('worker-ipc', ['jobs'], ['key' => $key]);

		// When we don't have a row, no job is running
		if (!DBA::isResult($row)) {
			return false;
		}

		return (bool) $row['jobs'];
	}
}
