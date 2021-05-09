<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Core;

use Friendica\App\Mode;
use Friendica\Core;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\DateTimeFormat;

/**
 * Contains the class for the worker background job processing
 */
class Worker
{
	const STATE_STARTUP    = 1; // Worker is in startup. This takes most time.
	const STATE_LONG_LOOP  = 2; // Worker is processing the whole - long - loop.
	const STATE_REFETCH    = 3; // Worker had refetched jobs in the execution loop.
	const STATE_SHORT_LOOP = 4; // Worker is processing preassigned jobs, thus saving much time.

	const FAST_COMMANDS = ['APDelivery', 'Delivery'];

	const LOCK_PROCESS = 'worker_process';
	const LOCK_WORKER = 'worker';

	private static $up_start;
	private static $db_duration = 0;
	private static $db_duration_count = 0;
	private static $db_duration_write = 0;
	private static $db_duration_stat = 0;
	private static $lock_duration = 0;
	private static $last_update;
	private static $state;
	private static $daemon_mode = null;

	/**
	 * Processes the tasks that are in the workerqueue table
	 *
	 * @param boolean $run_cron Should the cron processes be executed?
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function processQueue($run_cron = true)
	{
		// Ensure that all "strtotime" operations do run timezone independent
		date_default_timezone_set('UTC');

		self::$up_start = microtime(true);

		// At first check the maximum load. We shouldn't continue with a high load
		if (DI::process()->isMaxLoadReached()) {
			Logger::notice('Pre check: maximum load reached, quitting.');
			return;
		}

		// We now start the process. This is done after the load check since this could increase the load.
		DI::process()->start();

		// Kill stale processes every 5 minutes
		$last_cleanup = DI::config()->get('system', 'worker_last_cleaned', 0);
		if (time() > ($last_cleanup + 300)) {
			DI::config()->set('system', 'worker_last_cleaned', time());
			self::killStaleWorkers();
		}

		// Check if the system is ready
		if (!self::isReady()) {
			return;
		}

		// Now we start additional cron processes if we should do so
		if ($run_cron) {
			self::runCron();
		}

		$last_check = $starttime = time();
		self::$state = self::STATE_STARTUP;

		// We fetch the next queue entry that is about to be executed
		while ($r = self::workerProcess()) {
			if (self::IPCJobsExists(getmypid())) {
				self::IPCDeleteJobState(getmypid());
			}

			// Don't refetch when a worker fetches tasks for multiple workers
			$refetched = DI::config()->get('system', 'worker_multiple_fetch');
			foreach ($r as $entry) {
				// Assure that the priority is an integer value
				$entry['priority'] = (int)$entry['priority'];

				// The work will be done
				if (!self::execute($entry)) {
					Logger::notice('Process execution failed, quitting.');
					return;
				}

				// Trying to fetch new processes - but only once when successful
				if (!$refetched && DI::lock()->acquire(self::LOCK_PROCESS, 0)) {
					self::findWorkerProcesses();
					DI::lock()->release(self::LOCK_PROCESS);
					self::$state = self::STATE_REFETCH;
					$refetched = true;
				} else {
					self::$state = self::STATE_SHORT_LOOP;
				}
			}

			// To avoid the quitting of multiple workers only one worker at a time will execute the check
			if ((time() > $last_check + 5) && !self::getWaitingJobForPID()) {
				self::$state = self::STATE_LONG_LOOP;

				if (DI::lock()->acquire(self::LOCK_WORKER, 0)) {
				// Count active workers and compare them with a maximum value that depends on the load
					if (self::tooMuchWorkers()) {
						Logger::notice('Active worker limit reached, quitting.');
						DI::lock()->release(self::LOCK_WORKER);
						return;
					}

					// Check free memory
					if (DI::process()->isMinMemoryReached()) {
						Logger::warning('Memory limit reached, quitting.');
						DI::lock()->release(self::LOCK_WORKER);
						return;
					}
					DI::lock()->release(self::LOCK_WORKER);
				}
				$last_check = time();
			}

			// Quit the worker once every cron interval
			if (time() > ($starttime + (DI::config()->get('system', 'cron_interval') * 60))) {
				Logger::info('Process lifetime reached, respawning.');
				self::unclaimProcess();
				if (self::isDaemonMode()) {
					self::IPCSetJobState(true);
				} else {
					self::spawnWorker();
				}
				return;
			}
		}

		// Cleaning up. Possibly not needed, but it doesn't harm anything.
		if (self::isDaemonMode()) {
			self::IPCSetJobState(false);
		}
		Logger::info("Couldn't select a workerqueue entry, quitting process", ['pid' => getmypid()]);
	}

	/**
	 * Checks if the system is ready.
	 *
	 * Several system parameters like memory, connections and processes are checked.
	 *
	 * @return boolean
	 */
	public static function isReady()
	{
		// Count active workers and compare them with a maximum value that depends on the load
		if (self::tooMuchWorkers()) {
			Logger::notice('Active worker limit reached, quitting.');
			return false;
		}

		// Do we have too few memory?
		if (DI::process()->isMinMemoryReached()) {
			Logger::warning('Memory limit reached, quitting.');
			return false;
		}

		// Possibly there are too much database connections
		if (self::maxConnectionsReached()) {
			Logger::warning('Maximum connections reached, quitting.');
			return false;
		}

		// Possibly there are too much database processes that block the system
		if (DI::process()->isMaxProcessesReached()) {
			Logger::warning('Maximum processes reached, quitting.');
			return false;
		}

		return true;
	}

	/**
	 * Check if non executed tasks do exist in the worker queue
	 *
	 * @return boolean Returns "true" if tasks are existing
	 * @throws \Exception
	 */
	public static function entriesExists()
	{
		$stamp = (float)microtime(true);
		$exists = DBA::exists('workerqueue', ["NOT `done` AND `pid` = 0 AND `next_try` < ?", DateTimeFormat::utcNow()]);
		self::$db_duration += (microtime(true) - $stamp);
		return $exists;
	}

	/**
	 * Returns the number of deferred entries in the worker queue
	 *
	 * @return integer Number of deferred entries in the worker queue
	 * @throws \Exception
	 */
	private static function deferredEntries()
	{
		$stamp = (float)microtime(true);
		$count = DBA::count('workerqueue', ["NOT `done` AND `pid` = 0 AND `retrial` > ?", 0]);
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_count += (microtime(true) - $stamp);
		return $count;
	}

	/**
	 * Returns the number of non executed entries in the worker queue
	 *
	 * @return integer Number of non executed entries in the worker queue
	 * @throws \Exception
	 */
	private static function totalEntries()
	{
		$stamp = (float)microtime(true);
		$count = DBA::count('workerqueue', ['done' => false, 'pid' => 0]);
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_count += (microtime(true) - $stamp);
		return $count;
	}

	/**
	 * Returns the highest priority in the worker queue that isn't executed
	 *
	 * @return integer Number of active worker processes
	 * @throws \Exception
	 */
	private static function highestPriority()
	{
		$stamp = (float)microtime(true);
		$condition = ["`pid` = 0 AND NOT `done` AND `next_try` < ?", DateTimeFormat::utcNow()];
		$workerqueue = DBA::selectFirst('workerqueue', ['priority'], $condition, ['order' => ['priority']]);
		self::$db_duration += (microtime(true) - $stamp);
		if (DBA::isResult($workerqueue)) {
			return $workerqueue["priority"];
		} else {
			return 0;
		}
	}

	/**
	 * Returns if a process with the given priority is running
	 *
	 * @param integer $priority The priority that should be checked
	 *
	 * @return integer Is there a process running with that priority?
	 * @throws \Exception
	 */
	private static function processWithPriorityActive($priority)
	{
		$condition = ["`priority` <= ? AND `pid` != 0 AND NOT `done`", $priority];
		return DBA::exists('workerqueue', $condition);
	}

	/**
	 * Execute a worker entry
	 *
	 * @param array $queue Workerqueue entry
	 *
	 * @return boolean "true" if further processing should be stopped
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function execute($queue)
	{
		$mypid = getmypid();

		// Quit when in maintenance
		if (DI::config()->get('system', 'maintenance', false, true)) {
			Logger::notice("Maintenance mode - quit process", ['pid' => $mypid]);
			return false;
		}

		// Constantly check the number of parallel database processes
		if (DI::process()->isMaxProcessesReached()) {
			Logger::warning("Max processes reached for process", ['pid' => $mypid]);
			return false;
		}

		// Constantly check the number of available database connections to let the frontend be accessible at any time
		if (self::maxConnectionsReached()) {
			Logger::warning("Max connection reached for process", ['pid' => $mypid]);
			return false;
		}

		$argv = json_decode($queue['parameter'], true);
		if (!is_array($argv)) {
			$argv = [];
		}

		if (!empty($queue['command'])) {
			array_unshift($argv, $queue['command']);
		}

		if (empty($argv)) {
			Logger::warning('Parameter is empty', ['queue' => $queue]);
			return false;
		}

		// Check for existance and validity of the include file
		$include = $argv[0];

		if (method_exists(sprintf('Friendica\Worker\%s', $include), 'execute')) {
			// We constantly update the "executed" date every minute to avoid being killed too soon
			if (!isset(self::$last_update)) {
				self::$last_update = strtotime($queue["executed"]);
			}

			$age = (time() - self::$last_update) / 60;
			self::$last_update = time();

			if ($age > 1) {
				$stamp = (float)microtime(true);
				DBA::update('workerqueue', ['executed' => DateTimeFormat::utcNow()], ['pid' => $mypid, 'done' => false]);
				self::$db_duration += (microtime(true) - $stamp);
				self::$db_duration_write += (microtime(true) - $stamp);
			}

			array_shift($argv);

			self::execFunction($queue, $include, $argv, true);

			$stamp = (float)microtime(true);
			$condition = ["`id` = ? AND `next_try` < ?", $queue['id'], DateTimeFormat::utcNow()];
			if (DBA::update('workerqueue', ['done' => true], $condition)) {
				DI::config()->set('system', 'last_worker_execution', DateTimeFormat::utcNow());
			}
			self::$db_duration = (microtime(true) - $stamp);
			self::$db_duration_write += (microtime(true) - $stamp);

			return true;
		}

		// The script could be provided as full path or only with the function name
		if ($include == basename($include)) {
			$include = "include/".$include.".php";
		}

		if (!validate_include($include)) {
			Logger::warning("Include file is not valid", ['file' => $argv[0]]);
			$stamp = (float)microtime(true);
			DBA::delete('workerqueue', ['id' => $queue["id"]]);
			self::$db_duration = (microtime(true) - $stamp);
			self::$db_duration_write += (microtime(true) - $stamp);
			return true;
		}

		require_once $include;

		$funcname = str_replace(".php", "", basename($argv[0]))."_run";

		if (function_exists($funcname)) {
			// We constantly update the "executed" date every minute to avoid being killed too soon
			if (!isset(self::$last_update)) {
				self::$last_update = strtotime($queue["executed"]);
			}

			$age = (time() - self::$last_update) / 60;
			self::$last_update = time();

			if ($age > 1) {
				$stamp = (float)microtime(true);
				DBA::update('workerqueue', ['executed' => DateTimeFormat::utcNow()], ['pid' => $mypid, 'done' => false]);
				self::$db_duration += (microtime(true) - $stamp);
				self::$db_duration_write += (microtime(true) - $stamp);
			}

			self::execFunction($queue, $funcname, $argv, false);

			$stamp = (float)microtime(true);
			if (DBA::update('workerqueue', ['done' => true], ['id' => $queue["id"]])) {
				DI::config()->set('system', 'last_worker_execution', DateTimeFormat::utcNow());
			}
			self::$db_duration = (microtime(true) - $stamp);
			self::$db_duration_write += (microtime(true) - $stamp);
		} else {
			Logger::warning("Function does not exist", ['function' => $funcname]);
			$stamp = (float)microtime(true);
			DBA::delete('workerqueue', ['id' => $queue["id"]]);
			self::$db_duration = (microtime(true) - $stamp);
			self::$db_duration_write += (microtime(true) - $stamp);
		}

		return true;
	}

	/**
	 * Execute a function from the queue
	 *
	 * @param array   $queue       Workerqueue entry
	 * @param string  $funcname    name of the function
	 * @param array   $argv        Array of values to be passed to the function
	 * @param boolean $method_call boolean
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function execFunction($queue, $funcname, $argv, $method_call)
	{
		$a = DI::app();

		$cooldown = DI::config()->get("system", "worker_cooldown", 0);
		if ($cooldown > 0) {
			Logger::info('Pre execution cooldown.', ['priority' => $queue["priority"], 'id' => $queue["id"], 'cooldown' => $cooldown]);
			sleep($cooldown);
		}

		Logger::enableWorker($funcname);

		Logger::info("Process start.", ['priority' => $queue["priority"], 'id' => $queue["id"]]);

		$stamp = (float)microtime(true);

		// We use the callstack here to analyze the performance of executed worker entries.
		// For this reason the variables have to be initialized.
		DI::profiler()->reset();

		if (!in_array($queue['priority'], PRIORITIES)) {
			Logger::warning('Invalid priority', ['queue' => $queue, 'callstack' => System::callstack(20)]);
			$queue['priority'] = PRIORITY_MEDIUM;
		}

		$a->queue = $queue;

		$up_duration = microtime(true) - self::$up_start;

		// Reset global data to avoid interferences
		unset($_SESSION);

		// Set the workerLogger as new default logger
		if ($method_call) {
			call_user_func_array(sprintf('Friendica\Worker\%s::execute', $funcname), $argv);
		} else {
			$funcname($argv, count($argv));
		}

		Logger::disableWorker();

		unset($a->queue);

		$duration = (microtime(true) - $stamp);

		/* With these values we can analyze how effective the worker is.
		 * The database and rest time should be low since this is the unproductive time.
		 * The execution time is the productive time.
		 * By changing parameters like the maximum number of workers we can check the effectivness.
		*/
		$dbtotal = round(self::$db_duration, 2);
		$dbread  = round(self::$db_duration - (self::$db_duration_count + self::$db_duration_write + self::$db_duration_stat), 2);
		$dbcount = round(self::$db_duration_count, 2);
		$dbstat  = round(self::$db_duration_stat, 2);
		$dbwrite = round(self::$db_duration_write, 2);
		$dblock  = round(self::$lock_duration, 2);
		$rest    = round(max(0, $up_duration - (self::$db_duration + self::$lock_duration)), 2);
		$exec    = round($duration, 2);

		Logger::info('Performance:', ['state' => self::$state, 'count' => $dbcount, 'stat' => $dbstat, 'write' => $dbwrite, 'lock' => $dblock, 'total' => $dbtotal, 'rest' => $rest, 'exec' => $exec]);

		self::$up_start = microtime(true);
		self::$db_duration = 0;
		self::$db_duration_count = 0;
		self::$db_duration_stat = 0;
		self::$db_duration_write = 0;
		self::$lock_duration = 0;

		if ($duration > 3600) {
			Logger::info('Longer than 1 hour.', ['priority' => $queue["priority"], 'id' => $queue["id"], 'duration' => round($duration/60, 3)]);
		} elseif ($duration > 600) {
			Logger::info('Longer than 10 minutes.', ['priority' => $queue["priority"], 'id' => $queue["id"], 'duration' => round($duration/60, 3)]);
		} elseif ($duration > 300) {
			Logger::info('Longer than 5 minutes.', ['priority' => $queue["priority"], 'id' => $queue["id"], 'duration' => round($duration/60, 3)]);
		} elseif ($duration > 120) {
			Logger::info('Longer than 2 minutes.', ['priority' => $queue["priority"], 'id' => $queue["id"], 'duration' => round($duration/60, 3)]);
		}

		Logger::info('Process done.', ['priority' => $queue["priority"], 'id' => $queue["id"], 'duration' => round($duration, 3)]);

		DI::profiler()->saveLog(DI::logger(), "ID " . $queue["id"] . ": " . $funcname);

		if ($cooldown > 0) {
			Logger::info('Post execution cooldown.', ['priority' => $queue["priority"], 'id' => $queue["id"], 'cooldown' => $cooldown]);
			sleep($cooldown);
		}
	}

	/**
	 * Checks if the number of database connections has reached a critical limit.
	 *
	 * @return bool Are more than 3/4 of the maximum connections used?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function maxConnectionsReached()
	{
		// Fetch the max value from the config. This is needed when the system cannot detect the correct value by itself.
		$max = DI::config()->get("system", "max_connections");

		// Fetch the percentage level where the worker will get active
		$maxlevel = DI::config()->get("system", "max_connections_level", 75);

		if ($max == 0) {
			// the maximum number of possible user connections can be a system variable
			$r = DBA::fetchFirst("SHOW VARIABLES WHERE `variable_name` = 'max_user_connections'");
			if (DBA::isResult($r)) {
				$max = $r["Value"];
			}
			// Or it can be granted. This overrides the system variable
			$stamp = (float)microtime(true);
			$r = DBA::p('SHOW GRANTS');
			self::$db_duration += (microtime(true) - $stamp);
			while ($grants = DBA::fetch($r)) {
				$grant = array_pop($grants);
				if (stristr($grant, "GRANT USAGE ON")) {
					if (preg_match("/WITH MAX_USER_CONNECTIONS (\d*)/", $grant, $match)) {
						$max = $match[1];
					}
				}
			}
			DBA::close($r);
		}

		// If $max is set we will use the processlist to determine the current number of connections
		// The processlist only shows entries of the current user
		if ($max != 0) {
			$stamp = (float)microtime(true);
			$r = DBA::p('SHOW PROCESSLIST');
			self::$db_duration += (microtime(true) - $stamp);
			$used = DBA::numRows($r);
			DBA::close($r);

			Logger::info("Connection usage (user values)", ['usage' => $used, 'max' => $max]);

			$level = ($used / $max) * 100;

			if ($level >= $maxlevel) {
				Logger::warning("Maximum level (".$maxlevel."%) of user connections reached: ".$used."/".$max);
				return true;
			}
		}

		// We will now check for the system values.
		// This limit could be reached although the user limits are fine.
		$r = DBA::fetchFirst("SHOW VARIABLES WHERE `variable_name` = 'max_connections'");
		if (!DBA::isResult($r)) {
			return false;
		}
		$max = intval($r["Value"]);
		if ($max == 0) {
			return false;
		}
		$r = DBA::fetchFirst("SHOW STATUS WHERE `variable_name` = 'Threads_connected'");
		if (!DBA::isResult($r)) {
			return false;
		}
		$used = intval($r["Value"]);
		if ($used == 0) {
			return false;
		}
		Logger::info("Connection usage (system values)", ['used' => $used, 'max' => $max]);

		$level = $used / $max * 100;

		if ($level < $maxlevel) {
			return false;
		}
		Logger::warning("Maximum level (".$level."%) of system connections reached: ".$used."/".$max);
		return true;
	}

	/**
	 * fix the queue entry if the worker process died
	 *
	 * @return void
	 * @throws \Exception
	 */
	private static function killStaleWorkers()
	{
		$stamp = (float)microtime(true);
		$entries = DBA::select(
			'workerqueue',
			['id', 'pid', 'executed', 'priority', 'command', 'parameter'],
			['NOT `done` AND `pid` != 0'],
			['order' => ['priority', 'retrial', 'created']]
		);
		self::$db_duration += (microtime(true) - $stamp);

		while ($entry = DBA::fetch($entries)) {
			if (!posix_kill($entry["pid"], 0)) {
				$stamp = (float)microtime(true);
				DBA::update(
					'workerqueue',
					['executed' => DBA::NULL_DATETIME, 'pid' => 0],
					['id' => $entry["id"]]
				);
				self::$db_duration += (microtime(true) - $stamp);
				self::$db_duration_write += (microtime(true) - $stamp);
			} else {
				// Kill long running processes
				// Check if the priority is in a valid range
				if (!in_array($entry["priority"], [PRIORITY_CRITICAL, PRIORITY_HIGH, PRIORITY_MEDIUM, PRIORITY_LOW, PRIORITY_NEGLIGIBLE])) {
					$entry["priority"] = PRIORITY_MEDIUM;
				}

				// Define the maximum durations
				$max_duration_defaults = [PRIORITY_CRITICAL => 720, PRIORITY_HIGH => 10, PRIORITY_MEDIUM => 60, PRIORITY_LOW => 180, PRIORITY_NEGLIGIBLE => 720];
				$max_duration = $max_duration_defaults[$entry["priority"]];

				$argv = json_decode($entry['parameter'], true);
				if (!empty($entry['command'])) {
					$command = $entry['command'];
				} elseif (!empty($argv)) {
					$command = array_shift($argv);
				} else {
					return;
				}

				$command = basename($command);

				// How long is the process already running?
				$duration = (time() - strtotime($entry["executed"])) / 60;
				if ($duration > $max_duration) {
					Logger::notice('Worker process took too much time - killed', ['duration' => number_format($duration, 3), 'max' => $max_duration, 'id' => $entry["id"], 'pid' => $entry["pid"], 'command' => $command]);
					posix_kill($entry["pid"], SIGTERM);

					// We killed the stale process.
					// To avoid a blocking situation we reschedule the process at the beginning of the queue.
					// Additionally we are lowering the priority. (But not PRIORITY_CRITICAL)
					$new_priority = $entry["priority"];
					if ($entry["priority"] == PRIORITY_HIGH) {
						$new_priority = PRIORITY_MEDIUM;
					} elseif ($entry["priority"] == PRIORITY_MEDIUM) {
						$new_priority = PRIORITY_LOW;
					} elseif ($entry["priority"] != PRIORITY_CRITICAL) {
						$new_priority = PRIORITY_NEGLIGIBLE;
					}
					$stamp = (float)microtime(true);
					DBA::update(
						'workerqueue',
						['executed' => DBA::NULL_DATETIME, 'created' => DateTimeFormat::utcNow(), 'priority' => $new_priority, 'pid' => 0],
						['id' => $entry["id"]]
					);
					self::$db_duration += (microtime(true) - $stamp);
					self::$db_duration_write += (microtime(true) - $stamp);
				} else {
					Logger::info('Process runtime is okay', ['duration' => number_format($duration, 3), 'max' => $max_duration, 'id' => $entry["id"], 'pid' => $entry["pid"], 'command' => $command]);
				}
			}
		}
		DBA::close($entries);
	}

	/**
	 * Checks if the number of active workers exceeds the given limits
	 *
	 * @return bool Are there too much workers running?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function tooMuchWorkers()
	{
		$queues = DI::config()->get("system", "worker_queues", 10);

		$maxqueues = $queues;

		$active = self::activeWorkers();

		// Decrease the number of workers at higher load
		$load = System::currentLoad();
		if ($load) {
			$maxsysload = intval(DI::config()->get("system", "maxloadavg", 20));

			/* Default exponent 3 causes queues to rapidly decrease as load increases.
			 * If you have 20 max queues at idle, then you get only 5 queues at 37.1% of $maxsysload.
			 * For some environments, this rapid decrease is not needed.
			 * With exponent 1, you could have 20 max queues at idle and 13 at 37% of $maxsysload.
			 */
			$exponent = intval(DI::config()->get('system', 'worker_load_exponent', 3));
			$slope = pow(max(0, $maxsysload - $load) / $maxsysload, $exponent);
			$queues = intval(ceil($slope * $maxqueues));

			$processlist = '';

			if (DI::config()->get('system', 'worker_jpm')) {
				$intervals = explode(',', DI::config()->get('system', 'worker_jpm_range'));
				$jobs_per_minute = [];
				foreach ($intervals as $interval) {
					if ($interval == 0) {
						continue;
					} else {
						$interval = (int)$interval;
					}

					$stamp = (float)microtime(true);
					$jobs = DBA::p("SELECT COUNT(*) AS `jobs` FROM `workerqueue` WHERE `done` AND `executed` > UTC_TIMESTAMP() - INTERVAL ? MINUTE", $interval);
					self::$db_duration += (microtime(true) - $stamp);
					self::$db_duration_stat += (microtime(true) - $stamp);
					if ($job = DBA::fetch($jobs)) {
						$jobs_per_minute[$interval] = number_format($job['jobs'] / $interval, 0);
					}
					DBA::close($jobs);
				}
				$processlist = ' - jpm: '.implode('/', $jobs_per_minute);
			}

			// Create a list of queue entries grouped by their priority
			$listitem = [0 => ''];

			$idle_workers = $active;

			$deferred = self::deferredEntries();

			if (DI::config()->get('system', 'worker_debug')) {
				$waiting_processes = 0;
				// Now adding all processes with workerqueue entries
				$stamp = (float)microtime(true);
				$jobs = DBA::p("SELECT COUNT(*) AS `entries`, `priority` FROM `workerqueue` WHERE NOT `done` GROUP BY `priority`");
				self::$db_duration += (microtime(true) - $stamp);
				self::$db_duration_stat += (microtime(true) - $stamp);
				while ($entry = DBA::fetch($jobs)) {
					$stamp = (float)microtime(true);
					$processes = DBA::p("SELECT COUNT(*) AS `running` FROM `workerqueue-view` WHERE `priority` = ?", $entry["priority"]);
					self::$db_duration += (microtime(true) - $stamp);
					self::$db_duration_stat += (microtime(true) - $stamp);
					if ($process = DBA::fetch($processes)) {
						$idle_workers -= $process["running"];
						$waiting_processes += $entry["entries"];
						$listitem[$entry["priority"]] = $entry["priority"].":".$process["running"]."/".$entry["entries"];
					}
					DBA::close($processes);
				}
				DBA::close($jobs);
			} else {
				$waiting_processes =  self::totalEntries();
				$stamp = (float)microtime(true);
				$jobs = DBA::p("SELECT COUNT(*) AS `running`, `priority` FROM `workerqueue-view` GROUP BY `priority` ORDER BY `priority`");
				self::$db_duration += (microtime(true) - $stamp);
				self::$db_duration_stat += (microtime(true) - $stamp);

				while ($entry = DBA::fetch($jobs)) {
					$idle_workers -= $entry["running"];
					$listitem[$entry["priority"]] = $entry["priority"].":".$entry["running"];
				}
				DBA::close($jobs);
			}

			$waiting_processes -= $deferred;

			$listitem[0] = "0:" . max(0, $idle_workers);

			$processlist .= ' ('.implode(', ', $listitem).')';

			if (DI::config()->get("system", "worker_fastlane", false) && ($queues > 0) && ($active >= $queues) && self::entriesExists()) {
				$top_priority = self::highestPriority();
				$high_running = self::processWithPriorityActive($top_priority);

				if (!$high_running && ($top_priority > PRIORITY_UNDEFINED) && ($top_priority < PRIORITY_NEGLIGIBLE)) {
					Logger::info("Jobs with a higher priority are waiting but none is executed. Open a fastlane.", ['priority' => $top_priority]);
					$queues = $active + 1;
				}
			}

			Logger::notice("Load: " . $load ."/" . $maxsysload . " - processes: " . $deferred . "/" . $active . "/" . $waiting_processes . $processlist . " - maximum: " . $queues . "/" . $maxqueues);

			// Are there fewer workers running as possible? Then fork a new one.
			if (!DI::config()->get("system", "worker_dont_fork", false) && ($queues > ($active + 1)) && self::entriesExists()) {
				Logger::info("There are fewer workers as possible, fork a new worker.", ['active' => $active, 'queues' => $queues]);
				if (self::isDaemonMode()) {
					self::IPCSetJobState(true);
				} else {
					self::spawnWorker();
				}
			}
		}

		// if there are too much worker, we don't spawn a new one.
		if (self::isDaemonMode() && ($active > $queues)) {
			self::IPCSetJobState(false);
		}

		return $active > $queues;
	}

	/**
	 * Returns the number of active worker processes
	 *
	 * @return integer Number of active worker processes
	 * @throws \Exception
	 */
	private static function activeWorkers()
	{
		$stamp = (float)microtime(true);
		$count = DBA::count('process', ['command' => 'Worker.php']);
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_count += (microtime(true) - $stamp);
		return $count;
	}

	/**
	 * Returns the number of active worker processes
	 *
	 * @return array List of worker process ids
	 * @throws \Exception
	 */
	private static function getWorkerPIDList()
	{
		$ids = [];
		$stamp = (float)microtime(true);

		$queues = DBA::p("SELECT `process`.`pid`, COUNT(`workerqueue`.`pid`) AS `entries` FROM `process`
			LEFT JOIN `workerqueue` ON `workerqueue`.`pid` = `process`.`pid` AND NOT `workerqueue`.`done` 
			GROUP BY `process`.`pid`");
		while ($queue = DBA::fetch($queues)) {
			$ids[$queue['pid']] = $queue['entries'];
		}
		DBA::close($queues);

		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_count += (microtime(true) - $stamp);
		return $ids;
	}

	/**
	 * Returns waiting jobs for the current process id
	 *
	 * @return array waiting workerqueue jobs
	 * @throws \Exception
	 */
	private static function getWaitingJobForPID()
	{
		$stamp = (float)microtime(true);
		$r = DBA::select('workerqueue', [], ['pid' => getmypid(), 'done' => false]);
		self::$db_duration += (microtime(true) - $stamp);
		if (DBA::isResult($r)) {
			return DBA::toArray($r);
		}
		DBA::close($r);

		return false;
	}

	/**
	 * Returns the next jobs that should be executed
	 * @param int $limit
	 * @return array array with next jobs
	 * @throws \Exception
	 */
	private static function nextProcess(int $limit)
	{
		$priority = self::nextPriority();
		if (empty($priority)) {
			Logger::info('No tasks found');
			return [];
		}

		$ids = [];
		$stamp = (float)microtime(true);
		$condition = ["`priority` = ? AND `pid` = 0 AND NOT `done` AND `next_try` < ?", $priority, DateTimeFormat::utcNow()];
		$tasks = DBA::select('workerqueue', ['id', 'command', 'parameter'], $condition, ['limit' => $limit, 'order' => ['retrial', 'created']]);
		self::$db_duration += (microtime(true) - $stamp);
		while ($task = DBA::fetch($tasks)) {
			$ids[] = $task['id'];
			// Only continue that loop while we are storing commands that can be processed quickly
			if (!empty($task['command'])) {
				$command = $task['command'];
			} else {
				$command = json_decode($task['parameter'])[0];
			}

			if (!in_array($command, self::FAST_COMMANDS)) {
				break;
			}
		}
		DBA::close($tasks);

		Logger::info('Found:', ['priority' => $priority, 'id' => $ids]);
		return $ids;
	}

	/**
	 * Returns the priority of the next workerqueue job
	 *
	 * @return string priority
	 * @throws \Exception
	 */
	private static function nextPriority()
	{
		$waiting = [];
		$priorities = [PRIORITY_CRITICAL, PRIORITY_HIGH, PRIORITY_MEDIUM, PRIORITY_LOW, PRIORITY_NEGLIGIBLE];
		foreach ($priorities as $priority) {
			$stamp = (float)microtime(true);
			if (DBA::exists('workerqueue', ["`priority` = ? AND `pid` = 0 AND NOT `done` AND `next_try` < ?", $priority, DateTimeFormat::utcNow()])) {
				$waiting[$priority] = true;
			}
			self::$db_duration += (microtime(true) - $stamp);
		}

		if (!empty($waiting[PRIORITY_CRITICAL])) {
			return PRIORITY_CRITICAL;
		}

		$running = [];
		$running_total = 0;
		$stamp = (float)microtime(true);
		$processes = DBA::p("SELECT COUNT(DISTINCT(`pid`)) AS `running`, `priority` FROM `workerqueue-view` GROUP BY `priority`");
		self::$db_duration += (microtime(true) - $stamp);
		while ($process = DBA::fetch($processes)) {
			$running[$process['priority']] = $process['running'];
			$running_total += $process['running'];
		}
		DBA::close($processes);

		foreach ($priorities as $priority) {
			if (!empty($waiting[$priority]) && empty($running[$priority])) {
				Logger::info('No running worker found with priority {priority} - assigning it.', ['priority' => $priority]);
				return $priority;
			}
		}

		$active = max(self::activeWorkers(), $running_total);
		$priorities = max(count($waiting), count($running));
		$exponent = 2;

		$total = 0;
		for ($i = 1; $i <= $priorities; ++$i) {
			$total += pow($i, $exponent);
		}

		$limit = [];
		for ($i = 1; $i <= $priorities; ++$i) {
			$limit[$priorities - $i] = max(1, round($active * (pow($i, $exponent) / $total)));
		}

		$i = 0;
		foreach ($running as $priority => $workers) {
			if ($workers < $limit[$i++]) {
				Logger::info('Priority {priority} has got {workers} workers out of a limit of {limit}', ['priority' => $priority, 'workers' => $workers, 'limit' => $limit[$i - 1]]);
				return $priority;
			}
		}

		if (!empty($waiting)) {
			$priority = array_keys($waiting)[0];
			Logger::info('No underassigned priority found, now taking the highest priority.', ['priority' => $priority]);
			return $priority;
		}

		return false;
	}

	/**
	 * Find and claim the next worker process for us
	 *
	 * @return boolean Have we found something?
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function findWorkerProcesses()
	{
		$fetch_limit = DI::config()->get('system', 'worker_fetch_limit', 1);

		if (DI::config()->get('system', 'worker_multiple_fetch')) {
			$pids = [];
			foreach (self::getWorkerPIDList() as $pid => $count) {
				if ($count <= $fetch_limit) {
					$pids[] = $pid;
				}
			}
			if (empty($pids)) {
				return;
			}
			$limit = $fetch_limit * count($pids);
		} else {
			$pids = [getmypid()];
			$limit = $fetch_limit;
		}

		$ids = self::nextProcess($limit);
		$limit -= count($ids);

		// If there is not enough results we check without priority limit
		if ($limit > 0) {
			$stamp = (float)microtime(true);
			$condition = ["`pid` = 0 AND NOT `done` AND `next_try` < ?", DateTimeFormat::utcNow()];
			$tasks = DBA::select('workerqueue', ['id', 'command', 'parameter'], $condition, ['limit' => $limit, 'order' => ['priority', 'retrial', 'created']]);
			self::$db_duration += (microtime(true) - $stamp);

			while ($task = DBA::fetch($tasks)) {
				$ids[] = $task['id'];
				// Only continue that loop while we are storing commands that can be processed quickly
				if (!empty($task['command'])) {
					$command = $task['command'];
				} else {
					$command = json_decode($task['parameter'])[0];
				}
				if (!in_array($command, self::FAST_COMMANDS)) {
					break;
				}
			}
			DBA::close($tasks);
		}

		if (empty($ids)) {
			return;
		}

		// Assign the task ids to the workers
		$worker = [];
		foreach (array_unique($ids) as $id) {
			$pid = next($pids);
			if (!$pid) {
				$pid = reset($pids);
			}
			$worker[$pid][] = $id;
		}

		$stamp = (float)microtime(true);
		foreach ($worker as $worker_pid => $worker_ids) {
			Logger::info('Set queue entry', ['pid' => $worker_pid, 'ids' => $worker_ids]);
			DBA::update('workerqueue', ['executed' => DateTimeFormat::utcNow(), 'pid' => $worker_pid],
				['id' => $worker_ids, 'done' => false, 'pid' => 0]);
		}
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_write += (microtime(true) - $stamp);
	}

	/**
	 * Returns the next worker process
	 *
	 * @return array worker processes
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function workerProcess()
	{
		// There can already be jobs for us in the queue.
		$waiting = self::getWaitingJobForPID();
		if (!empty($waiting)) {
			return $waiting;
		}

		$stamp = (float)microtime(true);
		if (!DI::lock()->acquire(self::LOCK_PROCESS)) {
			return false;
		}
		self::$lock_duration += (microtime(true) - $stamp);

		self::findWorkerProcesses();

		DI::lock()->release(self::LOCK_PROCESS);

		return self::getWaitingJobForPID();
	}

	/**
	 * Removes a workerqueue entry from the current process
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function unclaimProcess()
	{
		$mypid = getmypid();

		$stamp = (float)microtime(true);
		DBA::update('workerqueue', ['executed' => DBA::NULL_DATETIME, 'pid' => 0], ['pid' => $mypid, 'done' => false]);
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_write += (microtime(true) - $stamp);
	}

	/**
	 * Runs the cron processes
	 *
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function runCron()
	{
		Logger::info('Add cron entries');

		// Check for spooled items
		self::add(['priority' => PRIORITY_HIGH, 'force_priority' => true], 'SpoolPost');

		// Run the cron job that calls all other jobs
		self::add(['priority' => PRIORITY_MEDIUM, 'force_priority' => true], 'Cron');

		// Cleaning dead processes
		self::killStaleWorkers();
	}

	/**
	 * Fork a child process
	 *
	 * @param boolean $do_cron
	 * @return void
	 */
	private static function forkProcess(bool $do_cron)
	{
		if (DI::process()->isMinMemoryReached()) {
			Logger::warning('Memory limit reached - quitting');
			return;
		}

		// Children inherit their parent's database connection.
		// To avoid problems we disconnect and connect both parent and child
		DBA::disconnect();
		$pid = pcntl_fork();
		if ($pid == -1) {
			DBA::connect();
			Logger::warning('Could not spawn worker');
			return;
		} elseif ($pid) {
			// The parent process continues here
			DBA::connect();

			self::IPCSetJobState(true, $pid);
			Logger::info('Spawned new worker', ['pid' => $pid]);

			$cycles = 0;
			while (self::IPCJobsExists($pid) && (++$cycles < 100)) {
				usleep(10000);
			}

			Logger::info('Spawned worker is ready', ['pid' => $pid, 'wait_cycles' => $cycles]);
			return;
		}

		// We now are in the new worker
		$pid = getmypid();

		DBA::connect();
		/// @todo Reinitialize the logger to set a new process_id and uid
		DI::process()->setPid($pid);

		$cycles = 0;
		while (!self::IPCJobsExists($pid) && (++$cycles < 100)) {
			usleep(10000);
		}

		Logger::info('Worker spawned', ['pid' => $pid, 'wait_cycles' => $cycles]);

		self::processQueue($do_cron);

		self::unclaimProcess();

		self::IPCSetJobState(false, $pid);
		DI::process()->end();
		Logger::info('Worker ended', ['pid' => $pid]);
		exit();
	}

	/**
	 * Spawns a new worker
	 *
	 * @param bool $do_cron
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function spawnWorker($do_cron = false)
	{
		if (self::isDaemonMode() && DI::config()->get('system', 'worker_fork')) {
			self::forkProcess($do_cron);
		} else {
			$process = new Core\Process(DI::logger(), DI::mode(), DI::config(),
				DI::modelProcess(), DI::app()->getBasePath(), getmypid());
			$process->run('bin/worker.php', ['no_cron' => !$do_cron]);
		}
		if (self::isDaemonMode()) {
			self::IPCSetJobState(false);
		}
	}

	/**
	 * Adds tasks to the worker queue
	 *
	 * @param (integer|array) priority or parameter array, strings are deprecated and are ignored
	 *
	 * next args are passed as $cmd command line
	 * or: Worker::add(PRIORITY_HIGH, "Notifier", Delivery::DELETION, $drop_id);
	 * or: Worker::add(array('priority' => PRIORITY_HIGH, 'dont_fork' => true), "Delivery", $post_id);
	 *
	 * @return boolean "false" if worker queue entry already existed or there had been an error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @note $cmd and string args are surrounded with ""
	 *
	 * @hooks 'proc_run'
	 *    array $arr
	 *
	 */
	public static function add($cmd)
	{
		$args = func_get_args();

		if (!count($args)) {
			return false;
		}

		$arr = ['args' => $args, 'run_cmd' => true];

		Hook::callAll("proc_run", $arr);
		if (!$arr['run_cmd'] || !count($args)) {
			return true;
		}

		$priority = PRIORITY_MEDIUM;
		// Don't fork from frontend tasks by default
		$dont_fork = DI::config()->get("system", "worker_dont_fork", false) || !DI::mode()->isBackend();
		$created = DateTimeFormat::utcNow();
		$delayed = DBA::NULL_DATETIME;
		$force_priority = false;

		$run_parameter = array_shift($args);

		if (is_int($run_parameter)) {
			$priority = $run_parameter;
		} elseif (is_array($run_parameter)) {
			if (isset($run_parameter['delayed'])) {
				$delayed = $run_parameter['delayed'];
			}
			if (isset($run_parameter['priority'])) {
				$priority = $run_parameter['priority'];
			}
			if (isset($run_parameter['created'])) {
				$created = $run_parameter['created'];
			}
			if (isset($run_parameter['dont_fork'])) {
				$dont_fork = $run_parameter['dont_fork'];
			}
			if (isset($run_parameter['force_priority'])) {
				$force_priority = $run_parameter['force_priority'];
			}
		}

		$command = array_shift($args);
		$parameters = json_encode($args);
		$found = DBA::exists('workerqueue', ['command' => $command, 'parameter' => $parameters, 'done' => false]);
		$added = false;

		if (!in_array($priority, PRIORITIES)) {
			Logger::warning('Invalid priority', ['priority' => $priority, 'command' => $command, 'callstack' => System::callstack(20)]);
			$priority = PRIORITY_MEDIUM;
		}

		// Quit if there was a database error - a precaution for the update process to 3.5.3
		if (DBA::errorNo() != 0) {
			return false;
		}

		if (!$found) {
			$added = DBA::insert('workerqueue', ['command' => $command, 'parameter' => $parameters, 'created' => $created,
				'priority' => $priority, 'next_try' => $delayed]);
			if (!$added) {
				return false;
			}
		} elseif ($force_priority) {
			DBA::update('workerqueue', ['priority' => $priority], ['command' => $command, 'parameter' => $parameters, 'done' => false, 'pid' => 0]);
		}

		// Set the IPC flag to ensure an immediate process execution via daemon
		if (self::isDaemonMode()) {
			self::IPCSetJobState(true);
		}

		self::checkDaemonState();

		// Should we quit and wait for the worker to be called as a cronjob?
		if ($dont_fork) {
			return $added;
		}

		// If there is a lock then we don't have to check for too much worker
		if (!DI::lock()->acquire(self::LOCK_WORKER, 0)) {
			return $added;
		}

		// If there are already enough workers running, don't fork another one
		$quit = self::tooMuchWorkers();
		DI::lock()->release(self::LOCK_WORKER);

		if ($quit) {
			return $added;
		}

		// Quit on daemon mode
		if (self::isDaemonMode()) {
			return $added;
		}

		// Now call the worker to execute the jobs that we just added to the queue
		self::spawnWorker();

		return $added;
	}

	public static function countWorkersByCommand(string $command)
	{
		return DBA::count('workerqueue', ['done' => false, 'pid' => 0, 'command' => $command]);
	}

	/**
	 * Returns the next retrial level for worker jobs.
	 * This function will skip levels when jobs are older.
	 *
	 * @param array $queue Worker queue entry
	 * @param integer $max_level maximum retrial level
	 * @return integer the next retrial level value
	 */
	private static function getNextRetrial($queue, $max_level)
	{
		$created = strtotime($queue['created']);
		$retrial_time = time() - $created;

		$new_retrial = $queue['retrial'] + 1;
		$total = 0;
		for ($retrial = 0; $retrial <= $max_level + 1; ++$retrial) {
			$delay = (($retrial + 3) ** 4) + (rand(1, 30) * ($retrial + 1));
			$total += $delay;
			if (($total < $retrial_time) && ($retrial > $queue['retrial'])) {
				$new_retrial = $retrial;
			}
		}
		Logger::notice('New retrial for task', ['id' => $queue['id'], 'created' => $queue['created'], 'old' => $queue['retrial'], 'new' => $new_retrial]);
		return $new_retrial;
	}

	/**
	 * Defers the current worker entry
	 *
	 * @return boolean had the entry been deferred?
	 */
	public static function defer()
	{
		if (empty(DI::app()->queue)) {
			return false;
		}

		$queue = DI::app()->queue;

		$retrial = $queue['retrial'];
		$id = $queue['id'];
		$priority = $queue['priority'];

		$max_level = DI::config()->get('system', 'worker_defer_limit');

		$new_retrial = self::getNextRetrial($queue, $max_level);

		if ($new_retrial > $max_level) {
			Logger::notice('The task exceeded the maximum retry count', ['id' => $id, 'created' => $queue['created'], 'old_prio' => $queue['priority'], 'old_retrial' => $queue['retrial'], 'max_level' => $max_level, 'retrial' => $new_retrial]);
			return false;
		}

		// Calculate the delay until the next trial
		$delay = (($new_retrial + 2) ** 4) + (rand(1, 30) * ($new_retrial));
		$next = DateTimeFormat::utc('now + ' . $delay . ' seconds');

		if (($priority < PRIORITY_MEDIUM) && ($new_retrial > 3)) {
			$priority = PRIORITY_MEDIUM;
		} elseif (($priority < PRIORITY_LOW) && ($new_retrial > 6)) {
			$priority = PRIORITY_LOW;
		} elseif (($priority < PRIORITY_NEGLIGIBLE) && ($new_retrial > 8)) {
			$priority = PRIORITY_NEGLIGIBLE;
		}

		Logger::info('Deferred task', ['id' => $id, 'retrial' => $new_retrial, 'created' => $queue['created'], 'next_execution' => $next, 'old_prio' => $queue['priority'], 'new_prio' => $priority]);

		$stamp = (float)microtime(true);
		$fields = ['retrial' => $new_retrial, 'next_try' => $next, 'executed' => DBA::NULL_DATETIME, 'pid' => 0, 'priority' => $priority];
		DBA::update('workerqueue', $fields, ['id' => $id]);
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_write += (microtime(true) - $stamp);

		return true;
	}

	/**
	 * Set the flag if some job is waiting
	 *
	 * @param boolean $jobs Is there a waiting job?
	 * @param int $key Key number
	 * @throws \Exception
	 */
	public static function IPCSetJobState(bool $jobs, int $key = 0)
	{
		$stamp = (float)microtime(true);
		DBA::replace('worker-ipc', ['jobs' => $jobs, 'key' => $key]);
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_write += (microtime(true) - $stamp);
	}

	/**
	 * Delete a key entry
	 *
	 * @param int $key Key number
	 * @throws \Exception
	 */
	public static function IPCDeleteJobState(int $key)
	{
		$stamp = (float)microtime(true);
		DBA::delete('worker-ipc', ['key' => $key]);
		self::$db_duration += (microtime(true) - $stamp);
		self::$db_duration_write += (microtime(true) - $stamp);
	}

	/**
	 * Checks if some worker job waits to be executed
	 *
	 * @param int $key Key number
	 * @return bool
	 * @throws \Exception
	 */
	public static function IPCJobsExists(int $key = 0)
	{
		$stamp = (float)microtime(true);
		$row = DBA::selectFirst('worker-ipc', ['jobs'], ['key' => $key]);
		self::$db_duration += (microtime(true) - $stamp);

		// When we don't have a row, no job is running
		if (!DBA::isResult($row)) {
			return false;
		}

		return (bool)$row['jobs'];
	}

	/**
	 * Checks if the worker is running in the daemon mode.
	 *
	 * @return boolean
	 */
	public static function isDaemonMode()
	{
		if (!is_null(self::$daemon_mode)) {
			return self::$daemon_mode;
		}

		if (DI::mode()->getExecutor() == Mode::DAEMON) {
			return true;
		}

		$daemon_mode = DI::config()->get('system', 'worker_daemon_mode', false, true);
		if ($daemon_mode) {
			return $daemon_mode;
		}

		if (!function_exists('pcntl_fork')) {
			self::$daemon_mode = false;
			return false;
		}

		$pidfile = DI::config()->get('system', 'pidfile');
		if (empty($pidfile)) {
			// No pid file, no daemon
			self::$daemon_mode = false;
			return false;
		}

		if (!is_readable($pidfile)) {
			// No pid file. We assume that the daemon had been intentionally stopped.
			self::$daemon_mode = false;
			return false;
		}

		$pid = intval(file_get_contents($pidfile));
		$running = posix_kill($pid, 0);

		self::$daemon_mode = $running;
		return $running;
	}

	/**
	 * Test if the daemon is running. If not, it will be started
	 *
	 * @return void
	 */
	private static function checkDaemonState()
	{
		if (!DI::config()->get('system', 'daemon_watchdog', false)) {
			return;
		}

		if (!DI::mode()->isNormal()) {
			return;
		}

		// Check every minute if the daemon is running
		if (DI::config()->get('system', 'last_daemon_check', 0) + 60 > time()) {
			return;
		}

		DI::config()->set('system', 'last_daemon_check', time());

		$pidfile = DI::config()->get('system', 'pidfile');
		if (empty($pidfile)) {
			// No pid file, no daemon
			return;
		}

		if (!is_readable($pidfile)) {
			// No pid file. We assume that the daemon had been intentionally stopped.
			return;
		}

		$pid = intval(file_get_contents($pidfile));
		if (posix_kill($pid, 0)) {
			Logger::info('Daemon process is running', ['pid' => $pid]);
			return;
		}

		Logger::warning('Daemon process is not running', ['pid' => $pid]);

		self::spawnDaemon();
	}

	/**
	 * Spawn a new daemon process
	 *
	 * @return void
	 */
	private static function spawnDaemon()
	{
		Logger::notice('Starting new daemon process');
		$command = 'bin/daemon.php';
		$a = DI::app();
		$process = new Core\Process(DI::logger(), DI::mode(), DI::config(), DI::modelProcess(), $a->getBasePath(), getmypid());
		$process->run($command, ['start']);
		Logger::notice('New daemon process started');
	}

	/**
	 * Check if the system is inside the defined maintenance window
	 *
	 * @return boolean
	 */
	public static function isInMaintenanceWindow(bool $check_last_execution = false)
	{
		// Calculate the seconds of the start end end of the maintenance window
		$start = strtotime(DI::config()->get('system', 'maintenance_start')) % 86400;
		$end = strtotime(DI::config()->get('system', 'maintenance_end')) % 86400;

		Logger::info('Maintenance window', ['start' => date('H:i:s', $start), 'end' => date('H:i:s', $end)]);

		if ($check_last_execution) {
			// Calculate the window duration
			$duration = max($start, $end) - min($start, $end);

			// Quit when the last cron execution had been after the previous window
			$last_cron = DI::config()->get('system', 'last_cron_daily');
			if ($last_cron + $duration > time()) {
				Logger::info('The Daily cron had been executed recently', ['last' => date(DateTimeFormat::MYSQL, $last_cron), 'start' => date('H:i:s', $start), 'end' => date('H:i:s', $end)]);
				return false;
			}
		}

		$current = time() % 86400;

		if ($start < $end) {
			// Execute if we are inside the window
			$execute = ($current >= $start) && ($current <= $end);
		} else {
			// Don't execute if we are outside the window
			$execute = !(($current > $end) && ($current < $start));
		}

		if ($execute) {
			Logger::info('We are inside the maintenance window', ['current' => date('H:i:s', $current), 'start' => date('H:i:s', $start), 'end' => date('H:i:s', $end)]);
		} else {
			Logger::info('We are outside the maintenance window', ['current' => date('H:i:s', $current), 'start' => date('H:i:s', $start), 'end' => date('H:i:s', $end)]);
		}
		
		return $execute;
	}
}
