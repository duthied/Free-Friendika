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

	if(($argc <= 1) OR ($argv[1] != "no_cron")) {
		// Run the cron job that calls all other jobs
		proc_run("php","include/cron.php");

		// Run the cronhooks job separately from cron for being able to use a different timing
		proc_run("php","include/cronhooks.php");

		// Cleaning dead processes
		$r = q("SELECT DISTINCT(`pid`) FROM `workerqueue` WHERE `executed` != '0000-00-00 00:00:00'");
		foreach($r AS $pid)
			if (!posix_kill($pid["pid"], 0))
				q("UPDATE `workerqueue` SET `executed` = '0000-00-00 00:00:00', `pid` = 0 WHERE `pid` = %d",
					intval($pid["pid"]));

	} else
		// Sleep two seconds before checking for running processes to avoid having too many workers
		sleep(4);

	// Checking number of workers
	$workers = q("SELECT COUNT(*) AS `workers` FROM `workerqueue` WHERE `executed` != '0000-00-00 00:00:00'");

	$queues = intval(get_config("system", "worker_queues"));

	if ($queues == 0)
		$queues = 4;

	if ($workers[0]["workers"] >= $queues)
		return;

	while ($r = q("SELECT * FROM `workerqueue` WHERE `executed` = '0000-00-00 00:00:00' ORDER BY `created` LIMIT 1")) {
		q("UPDATE `workerqueue` SET `executed` = '%s', `pid` = %d WHERE `id` = %d",
			dbesc(datetime_convert()),
			intval(getmypid()),
			intval($r[0]["id"]));

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
			logger("Process ".getmypid().": ".$funcname." ".$r[0]["parameter"]);
			$funcname($argv, $argc);

			logger("Process ".getmypid().": ".$funcname." - done");

			q("DELETE FROM `workerqueue` WHERE `id` = %d", intval($r[0]["id"]));
		} else
			logger("Function ".$funcname." does not exist");
	}

}

if (array_search(__file__,get_included_files())===0){
  poller_run($_SERVER["argv"],$_SERVER["argc"]);
  killme();
}
?>
