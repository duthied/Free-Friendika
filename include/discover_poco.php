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

	if(($argc > 2) && ($argv[1] == "dirsearch")) {
		$search = urldecode($argv[2]);
		$searchmode = 1;
	} elseif ($argc == 1) {
		$search = "";
		$searchmode = 0;
	} else
		die("Unknown or missing parameter ".$argv[1]."\n");

	$lockpath = get_lockpath();
	if ($lockpath != '') {
		$pidfile = new pidfile($lockpath, 'discover_poco'.urlencode($search));
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

	logger('start '.$search);

	if (($search != "") and get_config('system','poco_local_search'))
		discover_directory($search);
	elseif (($search == "") and get_config('system','poco_discovery') > 0)
		poco_discover();

	logger('end '.$search);

	return;
}

function discover_directory($search) {

	$data = Cache::get("dirsearch:".$search);
	if (!is_null($data)){
		// Only search for the same item every 24 hours
		if (time() < $data + (60 * 60 * 24)) {
			logger("Already searched for ".$search." in the last 24 hours", LOGGER_DEBUG);
			return;
		}
	}

	$x = fetch_url("http://dir.friendica.com/lsearch?p=1&n=500&search=".urlencode($search));
	$j = json_decode($x);

	if(count($j->results))
		foreach($j->results as $jj) {
			// Check if the contact already exists
			$exists = q("SELECT `id`, `last_contact`, `last_failure`  FROM `gcontact` WHERE `nurl` = '%s'", normalise_link($jj->url));
			if ($exists) {
				logger("Profile ".$jj->url." already exists (".$search.")", LOGGER_DEBUG);

				if ($exists[0]["last_contact"] < $exists[0]["last_failure"])
					continue;

				$last_updated = poco_last_updated($jj->url);
				$last_contact = datetime_convert();

				if ($last_updated) {
					logger("Mark profile ".$jj->url." as accessible (".$search.")", LOGGER_DEBUG);
					q("UPDATE `gcontact` SET `updated` = '%s', `last_contact` = '%s' WHERE `nurl` = '%s'",
						dbesc($last_updated), dbesc($last_contact), dbesc(normalise_link($jj->url)));
				} else {
					logger("Mark profile ".$jj->url." as unaccessible (".$search.")", LOGGER_DEBUG);
					q("UPDATE `gcontact` SET `last_failure` = '%s' WHERE `nurl` = '%s'",
						dbesc($last_contact), dbesc(normalise_link($jj->url)));
				}
				continue;
			}

			logger("Check if profile ".$jj->url." is reachable (".$search.")", LOGGER_DEBUG);
			$data = probe_url($jj->url);
			if ($data["network"] == NETWORK_DFRN) {
				logger("Add profile ".$jj->url." to local directory (".$search.")", LOGGER_DEBUG);
				poco_check($data["url"], $data["name"], $data["network"], $data["photo"], "", "", "", $jj->tags, $data["addr"], "", 0);
			}
		}
	Cache::set("dirsearch:".$search, time());
}

if (array_search(__file__,get_included_files())===0){
  discover_poco_run($_SERVER["argv"],$_SERVER["argc"]);
  killme();
}
