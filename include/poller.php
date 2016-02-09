<?php
if (!file_exists("boot.php") AND (sizeof($_SERVER["argv"]) != 0)) {
	$directory = dirname($_SERVER["argv"][0]);

	if (substr($directory, 0, 1) != "/")
		$directory = $_SERVER["PWD"]."/".$directory;

	$directory = realpath($directory."/..");

	chdir($directory);
}

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

	if (poller_max_connections_reached())
		return;

	$load = current_load();
	if($load) {
		$maxsysload = intval(get_config('system','maxloadavg'));
		if($maxsysload < 1)
			$maxsysload = 50;

		if(intval($load) > $maxsysload) {
			logger('system: load ' . $load . ' too high. poller deferred to next scheduled run.');
			return;
		}
	}

	// Checking the number of workers
	if (poller_too_much_workers(1)) {
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
	if (poller_too_much_workers(2))
		return;

	$starttime = time();

	while ($r = q("SELECT * FROM `workerqueue` WHERE `executed` = '0000-00-00 00:00:00' ORDER BY `created` LIMIT 1")) {

		// Count active workers and compare them with a maximum value that depends on the load
		if (poller_too_much_workers(3))
			return;

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

		$argv = json_decode($r[0]["parameter"]);

		$argc = count($argv);

		// Check for existance and validity of the include file
		$include = $argv[0];

		if (!validate_include($include)) {
			logger("Include file ".$argv[0]." is not valid!");
			q("DELETE FROM `workerqueue` WHERE `id` = %d", intval($r[0]["id"]));
			continue;
		}

		require_once($include);

		$funcname=str_replace(".php", "", basename($argv[0]))."_run";

		if (function_exists($funcname)) {
			logger("Process ".getmypid()." - ID ".$r[0]["id"].": ".$funcname." ".$r[0]["parameter"]);
			$funcname($argv, $argc);

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
	$r = q("SHOW VARIABLES WHERE `variable_name` = 'max_connections'");
	if (!$r)
		return false;

	$max = intval($r[0]["Value"]);
	if ($max == 0)
		return false;

	$r = q("SHOW STATUS WHERE `variable_name` = 'Threads_connected'");
	if (!$r)
		return false;

	$connected = intval($r[0]["Value"]);
	if ($connected == 0)
		return false;

	$level = $connected / $max;

	logger("Connection usage: ".$connected."/".$max, LOGGER_DEBUG);

	if ($level < (3/4))
		return false;

	logger("Maximum level (3/4) of connections reached: ".$connected."/".$max);
	return true;

}

/**
 * @brief fix the queue entry if the worker process died
 *
 */
function poller_kill_stale_workers() {
	$r = q("SELECT `pid`, `executed` FROM `workerqueue` WHERE `executed` != '0000-00-00 00:00:00'");
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

function poller_too_much_workers($stage) {

	$queues = get_config("system", "worker_queues");

	if ($queues == 0)
		$queues = 4;

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

		logger("Current load stage ".$stage.": ".$load." - maximum: ".$maxsysload." - current queues: ".$active." - maximum: ".$queues, LOGGER_DEBUG);

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
