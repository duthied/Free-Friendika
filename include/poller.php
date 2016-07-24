<?php
if (!file_exists("boot.php") AND (sizeof($_SERVER["argv"]) != 0)) {
	$directory = dirname($_SERVER["argv"][0]);

	if (substr($directory, 0, 1) != "/")
		$directory = $_SERVER["PWD"]."/".$directory;

	$directory = realpath($directory."/..");

	chdir($directory);
}

use \Friendica\Core\Config;
use \Friendica\Core\PConfig;

require_once("boot.php");

function poller_run(&$argv, &$argc){
	global $a, $db;

	if(is_null($a)) {
		$a = new App;
	}

	if(is_null($db)) {
		@include(".htconfig.php");
		require_once("include/dba.php");
		$db = new dba($db_host, $db_user, $db_pass, $db_data);
		unset($db_host, $db_user, $db_pass, $db_data);
	};

	if ($a->max_processes_reached())
		return;

	if (poller_max_connections_reached())
		return;

	if (App::maxload_reached())
		return;

	// Checking the number of workers
	if (poller_too_much_workers($entries, $queues, $maxqueues, $active)) {
		poller_kill_stale_workers();
		return;
	}

	if(($argc <= 1) OR ($argv[1] != "no_cron")) {
		// Run the cron job that calls all other jobs
		proc_run("php","include/cron.php");

		// Run the cronhooks job separately from cron for being able to use a different timing
		proc_run("php","include/cronhooks.php");

		// Cleaning dead processes
		poller_kill_stale_workers();
	} else
		// Sleep four seconds before checking for running processes again to avoid having too many workers
		sleep(4);

	// Checking number of workers
	if (poller_too_much_workers($entries, $queues, $maxqueues, $active))
		return;

	$cooldown = Config::get("system", "worker_cooldown", 0);

	$starttime = time();

	$delayed = 0;

	while ($r = q("SELECT * FROM `workerqueue` WHERE `executed` = '0000-00-00 00:00:00' ORDER BY `created` LIMIT 1")) {

		// Constantly check the number of parallel database processes
		if ($a->max_processes_reached())
			return;

		// Constantly check the number of available database connections to let the frontend be accessible at any time
		if (poller_max_connections_reached())
			return;

		// Count active workers and compare them with a maximum value that depends on the load
		if (poller_too_much_workers($entries, $queues, $maxqueues, $active))
			return;

		$argv = json_decode($r[0]["parameter"]);

		$argc = count($argv);

		$funcname = str_replace(".php", "", basename($argv[0]))."_run";

		// Define the processes that have priority over any other process
		/// @todo Better check for priority processes
		$priority = array("delivery_run", "notifier_run", "pubsubpublish_run");

		// Reserve a speed lane
		if (($active == ($queues - 1)) AND ($maxqueues >= 2) AND !in_array($funcname, $priority)) {
			logger("Delay call to '".$funcname."' to reserve a speed lane for high priority processes", LOGGER_DEBUG);
			$delay = true;
		}

		// Delay other processes if the system has a high load and there are many pending processes.
		// The "delayed" value is a safety mechanism for the case when there are no high priority processes in the queue.
		if (!$delay AND (($queues / $maxqueues) <= 0.5) AND (($entries > 100) AND ($delayed < 100)) AND !in_array($funcname, $priority)) {
			// Count the number of delayed processes
			++$delayed;
			logger("Delay call to '".$funcname."' for performance reasons (Delay ".$delayed.")", LOGGER_DEBUG);
			$delay = true;
		}

		if ($delay) {
			q("UPDATE `workerqueue` SET `created` = '%s' WHERE `id` = %d",
				dbesc(datetime_convert()),
				intval($r[0]["id"]));
			continue;
		}

		// If we delivered a process that has high priority we reset the delayed counter.
		// When we reached the limit we will process any entry until we reach a high priority process.
		if (in_array($funcname, $priority))
			$delayed = 0;

		q("UPDATE `workerqueue` SET `executed` = '%s', `pid` = %d WHERE `id` = %d AND `executed` = '0000-00-00 00:00:00'",
			dbesc(datetime_convert()),
			intval(getmypid()),
			intval($r[0]["id"]));

		// Assure that there are no tasks executed twice
		$id = q("SELECT `id` FROM `workerqueue` WHERE `id` = %d AND `pid` = %d",
			intval($r[0]["id"]),
			intval(getmypid()));
		if (!$id) {
			logger("Queue item ".$r[0]["id"]." was executed multiple times - skip this execution", LOGGER_DEBUG);
			continue;
		}

		// Check for existance and validity of the include file
		$include = $argv[0];

		if (!validate_include($include)) {
			logger("Include file ".$argv[0]." is not valid!");
			q("DELETE FROM `workerqueue` WHERE `id` = %d", intval($r[0]["id"]));
			continue;
		}

		require_once($include);

		if (function_exists($funcname)) {
			logger("Process ".getmypid()." - ID ".$r[0]["id"].": ".$funcname." ".$r[0]["parameter"]);
			$funcname($argv, $argc);

			if ($cooldown > 0) {
				logger("Process ".getmypid()." - ID ".$r[0]["id"].": ".$funcname." - in cooldown for ".$cooldown." seconds");
				sleep($cooldown);
			}

			logger("Process ".getmypid()." - ID ".$r[0]["id"].": ".$funcname." - done");

			q("DELETE FROM `workerqueue` WHERE `id` = %d", intval($r[0]["id"]));
		} else
			logger("Function ".$funcname." does not exist");

		// Quit the poller once every hour
		if (time() > ($starttime + 3600))
			return;
	}

}

/**
 * @brief Checks if the number of database connections has reached a critical limit.
 *
 * @return bool Are more than 3/4 of the maximum connections used?
 */
function poller_max_connections_reached() {

	// Fetch the max value from the config. This is needed when the system cannot detect the correct value by itself.
	$max = get_config("system", "max_connections");

	// Fetch the percentage level where the poller will get active
	$maxlevel = get_config("system", "max_connections_level");
	if ($maxlevel == 0)
		$maxlevel = 75;

	if ($max == 0) {
		// the maximum number of possible user connections can be a system variable
		$r = q("SHOW VARIABLES WHERE `variable_name` = 'max_user_connections'");
		if ($r)
			$max = $r[0]["Value"];

		// Or it can be granted. This overrides the system variable
		$r = q("SHOW GRANTS");
		if ($r)
			foreach ($r AS $grants) {
				$grant = array_pop($grants);
				if (stristr($grant, "GRANT USAGE ON"))
					if (preg_match("/WITH MAX_USER_CONNECTIONS (\d*)/", $grant, $match))
						$max = $match[1];
			}
	}

	// If $max is set we will use the processlist to determine the current number of connections
	// The processlist only shows entries of the current user
	if ($max != 0) {
		$r = q("SHOW PROCESSLIST");
		if (!$r)
			return false;

		$used = count($r);

		logger("Connection usage (user values): ".$used."/".$max, LOGGER_DEBUG);

		$level = ($used / $max) * 100;

		if ($level >= $maxlevel) {
			logger("Maximum level (".$maxlevel."%) of user connections reached: ".$used."/".$max);
			return true;
		}
	}

	// We will now check for the system values.
	// This limit could be reached although the user limits are fine.
	$r = q("SHOW VARIABLES WHERE `variable_name` = 'max_connections'");
	if (!$r)
		return false;

	$max = intval($r[0]["Value"]);
	if ($max == 0)
		return false;

	$r = q("SHOW STATUS WHERE `variable_name` = 'Threads_connected'");
	if (!$r)
		return false;

	$used = intval($r[0]["Value"]);
	if ($used == 0)
		return false;

	logger("Connection usage (system values): ".$used."/".$max, LOGGER_DEBUG);

	$level = $used / $max * 100;

	if ($level < $maxlevel)
		return false;

	logger("Maximum level (".$level."%) of system connections reached: ".$used."/".$max);
	return true;
}

/**
 * @brief fix the queue entry if the worker process died
 *
 */
function poller_kill_stale_workers() {
	$r = q("SELECT `pid`, `executed` FROM `workerqueue` WHERE `executed` != '0000-00-00 00:00:00'");

	if (!dbm::is_result($r)) {
		// No processing here needed
		return;
	}

	foreach($r AS $pid)
		if (!posix_kill($pid["pid"], 0))
			q("UPDATE `workerqueue` SET `executed` = '0000-00-00 00:00:00', `pid` = 0 WHERE `pid` = %d",
				intval($pid["pid"]));
		else {
			// Kill long running processes
			$duration = (time() - strtotime($pid["executed"])) / 60;
			if ($duration > 180) {
				logger("Worker process ".$pid["pid"]." took more than 3 hours. It will be killed now.");
				posix_kill($pid["pid"], SIGTERM);

				// Question: If a process is stale: Should we remove it or should we reschedule it?
				// By now we rescheduling it. It's maybe not the wisest decision?
				q("UPDATE `workerqueue` SET `executed` = '0000-00-00 00:00:00', `pid` = 0 WHERE `pid` = %d",
					intval($pid["pid"]));
			} else
				logger("Worker process ".$pid["pid"]." now runs for ".round($duration)." minutes. That's okay.", LOGGER_DEBUG);
		}
}

function poller_too_much_workers(&$entries, &$queues, &$maxqueues, &$active) {

	$entries = 0;

	$queues = get_config("system", "worker_queues");

	if ($queues == 0)
		$queues = 4;

	$maxqueues = $queues;

	$active = poller_active_workers();

	// Decrease the number of workers at higher load
	$load = current_load();
	if($load) {
		$maxsysload = intval(get_config('system','maxloadavg'));
		if($maxsysload < 1)
			$maxsysload = 50;

		$maxworkers = $queues;

		// Some magical mathemathics to reduce the workers
		$exponent = 3;
		$slope = $maxworkers / pow($maxsysload, $exponent);
		$queues = ceil($slope * pow(max(0, $maxsysload - $load), $exponent));

		$s = q("SELECT COUNT(*) AS `total` FROM `workerqueue` WHERE `executed` = '0000-00-00 00:00:00'");
		$entries = $s[0]["total"];

		logger("Current load: ".$load." - maximum: ".$maxsysload." - current queues: ".$active."/".$entries." - maximum: ".$queues."/".$maxqueues, LOGGER_DEBUG);

	}

	return($active >= $queues);
}

function poller_active_workers() {
	$workers = q("SELECT COUNT(*) AS `workers` FROM `workerqueue` WHERE `executed` != '0000-00-00 00:00:00'");

	return($workers[0]["workers"]);
}

if (array_search(__file__,get_included_files())===0){
  poller_run($_SERVER["argv"],$_SERVER["argc"]);
  killme();
}
?>
