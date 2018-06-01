#!/usr/bin/env php
<?php
/**
 * @file bin/daemon.php
 * @brief Run the worker from a daemon.
 *
 * This script was taken from http://php.net/manual/en/function.pcntl-fork.php
 */

use Friendica\App;
use Friendica\BaseObject;
use Friendica\Core\Config;
use Friendica\Core\Worker;

// Ensure that daemon.php is executed from the base path of the installation
if (!file_exists("boot.php") && (sizeof($_SERVER["argv"]) != 0)) {
	$directory = dirname($_SERVER["argv"][0]);

	if (substr($directory, 0, 1) != "/") {
		$directory = $_SERVER["PWD"]."/".$directory;
	}
	$directory = realpath($directory."/..");

	chdir($directory);
}

require_once "boot.php";
require_once "include/dba.php";

$a = new App(dirname(__DIR__));
BaseObject::setApp($a);

require_once ".htconfig.php";
dba::connect($db_host, $db_user, $db_pass, $db_data);

Config::load();

if (!isset($pidfile)) {
	die('Please specify a pid file in the variable $pidfile in the .htconfig.php. For example:'."\n".
		'$pidfile = "/path/to/daemon.pid";'."\n");
}

if (in_array("start", $_SERVER["argv"])) {
	$mode = "start";
}

if (in_array("stop", $_SERVER["argv"])) {
	$mode = "stop";
}

if (in_array("status", $_SERVER["argv"])) {
	$mode = "status";
}

if (!isset($mode)) {
	die("Please use either 'start', 'stop' or 'status'.\n");
}

if (empty($_SERVER["argv"][0])) {
	die("Unexpected script behaviour. This message should never occur.\n");
}

$pid = @file_get_contents($pidfile);

if (empty($pid) && in_array($mode, ["stop", "status"])) {
	Config::set('system', 'worker_daemon_mode', false);
	die("Pidfile wasn't found. Is the daemon running?\n");
}

if ($mode == "status") {
	if (posix_kill($pid, 0)) {
		die("Daemon process $pid is running.\n");
	}

	unlink($pidfile);

	Config::set('system', 'worker_daemon_mode', false);
	die("Daemon process $pid isn't running.\n");
}

if ($mode == "stop") {
	posix_kill($pid, SIGTERM);

	unlink($pidfile);

	logger("Worker daemon process $pid was killed.", LOGGER_DEBUG);

	Config::set('system', 'worker_daemon_mode', false);
	die("Worker daemon process $pid was killed.\n");
}

if (!empty($pid) && posix_kill($pid, 0)) {
	die("Daemon process $pid is already running.\n");
}

logger('Starting worker daemon.', LOGGER_DEBUG);
echo "Starting worker daemon.\n";

// Switch over to daemon mode.
if ($pid = pcntl_fork())
	return;     // Parent

fclose(STDIN);  // Close all of the standard
fclose(STDOUT); // file descriptors as we
fclose(STDERR); // are running as a daemon.

register_shutdown_function('shutdown');

if (posix_setsid() < 0)
	return;

if ($pid = pcntl_fork())
	return;     // Parent

// We lose the database connection upon forking
dba::connect($db_host, $db_user, $db_pass, $db_data);
unset($db_host, $db_user, $db_pass, $db_data);

Config::set('system', 'worker_daemon_mode', true);

// Just to be sure that this script really runs endlessly
set_time_limit(0);

$pid = getmypid();
file_put_contents($pidfile, $pid);

$wait_interval = intval(Config::get('system', 'cron_interval', 5)) * 60;

// Now running as a daemon.
while (true) {
	logger('Call the worker', LOGGER_DEBUG);
	Worker::spawnWorker();

	logger("Sleep for $wait_interval seconds - or when a worker needs to be called", LOGGER_DEBUG);
	$i = 0;
	do {
		sleep(1);
	} while (($i++ < $wait_interval) && !Worker::IPCJobsExists());
}

function shutdown() {
	posix_kill(posix_getpid(), SIGHUP);
}
