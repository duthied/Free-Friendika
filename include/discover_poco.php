<?php

require_once("boot.php");
require_once("include/socgraph.php");


function discover_poco_run(&$argv, &$argc){
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

	require_once('include/session.php');
	require_once('include/datetime.php');
	require_once('include/pidfile.php');

	load_config('config');
	load_config('system');

	$maxsysload = intval(get_config('system','maxloadavg'));
	if($maxsysload < 1)
		$maxsysload = 50;
	if(function_exists('sys_getloadavg')) {
		$load = sys_getloadavg();
		if(intval($load[0]) > $maxsysload) {
			logger('system: load ' . $load[0] . ' too high. discover_poco deferred to next scheduled run.');
			return;
		}
	}

	$lockpath = get_lockpath();
	if ($lockpath != '') {
		$pidfile = new pidfile($lockpath, 'discover_poco');
		if($pidfile->is_already_running()) {
			logger("discover_poco: Already running");
			if ($pidfile->running_time() > 19*60) {
                                $pidfile->kill();
                                logger("discover_poco: killed stale process");
				// Calling a new instance
				proc_run('php','include/discover_poco.php');
                        }
			exit;
		}
	}

	$a->set_baseurl(get_config('system','url'));

	load_hooks();

	logger('start');

	if (get_config('system','poco_discovery') > 0)
		poco_discover();

	logger('end');

	return;
}

if (array_search(__file__,get_included_files())===0){
  discover_poco_run($_SERVER["argv"],$_SERVER["argc"]);
  killme();
}
