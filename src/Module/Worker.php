<?php

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
