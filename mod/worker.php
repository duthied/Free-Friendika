<?php
require_once("include/poller.php");

use \Friendica\Core\Config;
use \Friendica\Core\PConfig;

function worker_init($a){

	if (!get_config("system", "frontend_worker")) {
		return;
	}

	clear_worker_processes();

	$workers = q("SELECT COUNT(*) AS `processes` FROM `process` WHERE `command` = 'worker.php'");

	if ($workers[0]["processes"] > Config::get("system", "worker_queues", 4)) {
		return;
	}

	$a->start_process();

	logger("Front end worker started: ".getmypid());

	call_worker();

	if ($r = poller_worker_process()) {
		poller_execute($r[0]);
	}

	call_worker();

	$a->end_process();

	logger("Front end worker ended: ".getmypid());

	killme();
}
