<?php
require_once("boot.php");
require_once("include/ostatus.php");

function handle_pubsubhubbub() {
	global $a, $db;

	logger('start');

	// We'll push to each subscriber that has push > 0,
	// i.e. there has been an update (set in notifier.php).

	$r = q("SELECT * FROM `push_subscriber` WHERE `push` > 0");

	foreach($r as $rr) {
		//$params = get_feed_for($a, '', $rr['nickname'], $rr['last_update'], 0, true);
		$params = ostatus_feed($a, $rr['nickname'], $rr['last_update']);
		$hmac_sig = hash_hmac("sha1", $params, $rr['secret']);

		$headers = array("Content-type: application/atom+xml",
				sprintf("Link: <%s>;rel=hub,<%s>;rel=self",
					$a->get_baseurl().'/pubsubhubbub',
					$rr['topic']),
				"X-Hub-Signature: sha1=".$hmac_sig);

		logger('POST '.print_r($headers, true)."\n".$params, LOGGER_DEBUG);

		post_url($rr['callback_url'], $params, $headers);
		$ret = $a->get_curl_code();

		if ($ret >= 200 && $ret <= 299) {
			logger('successfully pushed to '.$rr['callback_url']);

			// set last_update to "now", and reset push=0
			$date_now = datetime_convert('UTC','UTC','now','Y-m-d H:i:s');
			q("UPDATE `push_subscriber` SET `push` = 0, last_update = '%s' WHERE id = %d",
				dbesc($date_now),
				intval($rr['id']));

		} else {
			logger('error when pushing to '.$rr['callback_url'].' HTTP: '.$ret);

			// we use the push variable also as a counter, if we failed we
			// increment this until some upper limit where we give up
			$new_push = intval($rr['push']) + 1;

			if ($new_push > 30) // OK, let's give up
				$new_push = 0;

			q("UPDATE `push_subscriber` SET `push` = %d WHERE id = %d",
				$new_push,
				intval($rr['id']));
		}
	}

	logger('done');
}


function pubsubpublish_run(&$argv, &$argc){
	global $a, $db;

	if(is_null($a)){
		$a = new App;
	}

	if(is_null($db)){
		@include(".htconfig.php");
		require_once("include/dba.php");
		$db = new dba($db_host, $db_user, $db_pass, $db_data);
		unset($db_host, $db_user, $db_pass, $db_data);
	};

	require_once('include/items.php');
	require_once('include/pidfile.php');

	load_config('config');
	load_config('system');

	$lockpath = get_lockpath();
	if ($lockpath != '') {
		$pidfile = new pidfile($lockpath, 'pubsubpublish');
		if($pidfile->is_already_running()) {
			logger("Already running");
			if ($pidfile->running_time() > 9*60) {
				$pidfile->kill();
				logger("killed stale process");
				// Calling a new instance
				proc_run('php',"include/pubsubpublish.php");
			}
			return;
		}
	}

	$a->set_baseurl(get_config('system','url'));

	load_hooks();

	if($argc > 1)
		$pubsubpublish_id = intval($argv[1]);
	else
		$pubsubpublish_id = 0;

	handle_pubsubhubbub();

	return;

}

if (array_search(__file__,get_included_files())===0){
  pubsubpublish_run($_SERVER["argv"],$_SERVER["argc"]);
  killme();
}

