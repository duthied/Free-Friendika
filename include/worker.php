#!/usr/bin/php
<?php
if (sizeof($_SERVER["argv"]) == 0)
	die();

$directory = dirname($_SERVER["argv"][0]);

if (substr($directory, 0, 1) != "/")
	$directory = $_SERVER["PWD"]."/".$directory;

$directory = realpath($directory."/..");

chdir($directory);
require_once("boot.php");

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

// Cleaning killed processes
$r = q("SELECT DISTINCT(`pid`) FROM `workerqueue` WHERE `executed` != '0000-00-00 00:00:00'");
foreach($r AS $pid)
	if (!posix_kill($pid["pid"], 0))
		q("UPDATE `workerqueue` SET `executed` = '0000-00-00 00:00:00', `pid` = 0 WHERE `pid` = %d",
			intval($pid["pid"]));

// Checking number of workers
$workers = q("SELECT COUNT(*) AS `workers` FROM `workerqueue` WHERE `executed` != '0000-00-00 00:00:00'");

$threads = 3;

if ($workers[0]["workers"] >= $threads)
	return;

while ($r = q("SELECT * FROM `workerqueue` WHERE `executed` = '0000-00-00 00:00:00' ORDER BY `created` LIMIT 1")) {
	q("UPDATE `workerqueue` SET `executed` = '%s', `pid` = %d WHERE `id` = %d",
		dbesc(datetime_convert()),
		intval(getmypid()),
		intval($r[0]["id"]));

	$argv = json_decode($r[0]["parameter"]);

	$argc = count($argv);

	// To-Do: Check for existance
	require_once(basename($argv[0]));

	$funcname=str_replace(".php", "", basename($argv[0]))."_run";

	if (function_exists($funcname)) {
		logger("Process ".getmypid().": ".$funcname." ".$r[0]["parameter"]);
		//$funcname($argv, $argc);
		sleep(10);
		logger("Process ".getmypid().": ".$funcname." - done");

		q("DELETE FROM `workerqueue` WHERE `id` = %d", intval($r[0]["id"]));
	}
}
?>
