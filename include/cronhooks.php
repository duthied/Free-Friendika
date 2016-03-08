<?php

require_once("boot.php");


function cronhooks_run(&$argv, &$argc){
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

	load_config('config');
	load_config('system');

	// Don't check this stuff if the function is called by the poller
	if (App::callstack() != "poller_run") {
		if (App::maxload_reached())
			return;
		if (App::is_already_running('cronhooks', 'include/cronhooks.php', 1140))
			return;
	}

	$last = get_config('system','last_cronhook');

	$poll_interval = intval(get_config('system','cronhook_interval'));
	if(! $poll_interval)
		$poll_interval = 9;

	if($last) {
		$next = $last + ($poll_interval * 60);
		if($next > time()) {
			logger('cronhook intervall not reached');
			return;
		}
	}

	$a->set_baseurl(get_config('system','url'));

	load_hooks();

	logger('cronhooks: start');

	$d = datetime_convert();

	call_hooks('cron', $d);

	logger('cronhooks: end');

	set_config('system','last_cronhook', time());

	return;
}

if (array_search(__file__,get_included_files())===0){
	cronhooks_run($_SERVER["argv"],$_SERVER["argc"]);
	killme();
}
