<?php
use Friendica\App;
use Friendica\Core\Worker;
use Friendica\Core\Config;

if (!file_exists("boot.php") && (sizeof($_SERVER["argv"]) != 0)) {
	$directory = dirname($_SERVER["argv"][0]);

	if (substr($directory, 0, 1) != "/") {
		$directory = $_SERVER["PWD"]."/".$directory;
	}
	$directory = realpath($directory."/..");

	chdir($directory);
}

require_once("boot.php");

function poller_run($argv, $argc) {
	global $a;

	if (empty($a)) {
		$a = new App(dirname(__DIR__));
	}

	require_once ".htconfig.php";
	require_once "include/dba.php";
	dba::connect($db_host, $db_user, $db_pass, $db_data);
	unset($db_host, $db_user, $db_pass, $db_data);

	Config::load();

	// Check the database structure and possibly fixes it
	check_db(true);

	// Quit when in maintenance
	if (Config::get('system', 'maintenance', true)) {
		return;
	}

	$a->set_baseurl(Config::get('system', 'url'));

	load_hooks();

	$run_cron = (($argc <= 1) || ($argv[1] != "no_cron"));
	Worker::processQueue($run_cron);
	return;
}

if (array_search(__file__, get_included_files()) === 0) {
	poller_run($_SERVER["argv"], $_SERVER["argc"]);

	Worker::unclaimProcess();

	get_app()->end_process();

	killme();
}
