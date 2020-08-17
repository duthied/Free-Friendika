<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\Core\Worker as WorkerCore;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\DateTimeFormat;

/**
 * Module for starting the backend worker through a frontend call
 */
class Worker extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		if (!DI::config()->get("system", "frontend_worker")) {
			return;
		}

		// Ensure that all "strtotime" operations do run timezone independent
		date_default_timezone_set('UTC');

		// We don't need the following lines if we can execute background jobs.
		// So we just wake up the worker if it sleeps.
		if (function_exists("proc_open")) {
			WorkerCore::executeIfIdle();
			return;
		}

		WorkerCore::clearProcesses();

		$workers = DBA::count('process', ['command' => 'worker.php']);

		if ($workers > DI::config()->get("system", "worker_queues", 4)) {
			return;
		}

		WorkerCore::startProcess();

		DI::logger()->notice('Front end worker started.', ['pid' => getmypid()]);

		WorkerCore::callWorker();

		if ($r = WorkerCore::workerProcess()) {
			// On most configurations this parameter wouldn't have any effect.
			// But since it doesn't destroy anything, we just try to get more execution time in any way.
			set_time_limit(0);

			$fields = ['executed' => DateTimeFormat::utcNow(), 'pid' => getmypid(), 'done' => false];
			$condition =  ['id' => $r[0]["id"], 'pid' => 0];
			if (DBA::update('workerqueue', $fields, $condition)) {
				WorkerCore::execute($r[0]);
			}
		}

		WorkerCore::callWorker();

		WorkerCore::unclaimProcess();

		WorkerCore::endProcess();

		System::httpExit(200, 'Frontend worker stopped.');
	}
}
