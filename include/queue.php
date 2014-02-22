<?php
require_once("boot.php");
require_once('include/queue_fn.php');

function handle_pubsubhubbub() {
	global $a, $db;

	logger('queue [pubsubhubbub]: start');

	// We'll push to each subscriber that has push > 0,
	// i.e. there has been an update (set in notifier.php).

	$r = q("SELECT * FROM `push_subscriber` WHERE `push` > 0");

	foreach($r as $rr) {
		$params = get_feed_for($a, '', $rr['nickname'], $rr['last_update']);
		$hmac_sig = hash_hmac("sha1", $params, $rr['secret']);

		$headers = array("Content-type: application/atom+xml",
						 sprintf("Link: <%s>;rel=hub," .
								 "<%s>;rel=self",
								 $a->get_baseurl() . '/pubsubhubbub',
								 $rr['topic']),
						 "X-Hub-Signature: sha1=" . $hmac_sig);

		logger('queue [pubsubhubbub]: POST', $headers);

		post_url($rr['callback_url'], $params, $headers);
		$ret = $a->get_curl_code();

		if ($ret >= 200 && $ret <= 299) {
			logger('queue [pubsubhubbub]: successfully pushed to ' .
				   $rr['callback_url']);

			// set last_update to "now", and reset push=0
			$date_now = datetime_convert('UTC','UTC','now','Y-m-d H:i:s');
			q("UPDATE `push_subscriber` SET `push` = 0, last_update = '%s' " .
			  "WHERE id = %d",
			  dbesc($date_now),
			  intval($rr['id']));

		} else {
			logger('queue [pubsubhubbub]: error when pushing to ' .
				   $rr['callback_url'] . 'HTTP: ', $ret);

			// we use the push variable also as a counter, if we failed we
			// increment this until some upper limit where we give up
			$new_push = intval($rr['push']) + 1;
			
			if ($new_push > 30) // OK, let's give up
				$new_push = 0;

			q("UPDATE `push_subscriber` SET `push` = %d, last_update = '%s' " .
			  "WHERE id = %d",
			  $new_push,
			  dbesc($date_now),
			  intval($rr['id']));
		}
	}
}


function queue_run(&$argv, &$argc){
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


	require_once("include/session.php");
	require_once("include/datetime.php");
	require_once('include/items.php');
	require_once('include/bbcode.php');

	load_config('config');
	load_config('system');

	$a->set_baseurl(get_config('system','url'));

	load_hooks();

	if($argc > 1)
		$queue_id = intval($argv[1]);
	else
		$queue_id = 0;

	$deadguys = array();

	logger('queue: start');

	handle_pubsubhubbub();

	$interval = ((get_config('system','delivery_interval') === false) ? 2 : intval(get_config('system','delivery_interval')));

	$r = q("select * from deliverq where 1");
	if($r) {
		foreach($r as $rr) {
			logger('queue: deliverq');
			proc_run('php','include/delivery.php',$rr['cmd'],$rr['item'],$rr['contact']);
			if($interval)
				@time_sleep_until(microtime(true) + (float) $interval);
		}
	}

	$r = q("SELECT `queue`.*, `contact`.`name`, `contact`.`uid` FROM `queue` 
		LEFT JOIN `contact` ON `queue`.`cid` = `contact`.`id` 
		WHERE `queue`.`created` < UTC_TIMESTAMP() - INTERVAL 3 DAY");
	if($r) {
		foreach($r as $rr) {
			logger('Removing expired queue item for ' . $rr['name'] . ', uid=' . $rr['uid']);
			logger('Expired queue data :' . $rr['content'], LOGGER_DATA);
		}
		q("DELETE FROM `queue` WHERE `created` < UTC_TIMESTAMP() - INTERVAL 3 DAY");
	}
		
	if($queue_id) {
		$r = q("SELECT `id` FROM `queue` WHERE `id` = %d LIMIT 1",
			intval($queue_id)
		);
	}
	else {

		// For the first 12 hours we'll try to deliver every 15 minutes
		// After that, we'll only attempt delivery once per hour. 

		$r = q("SELECT `id` FROM `queue` WHERE (( `created` > UTC_TIMESTAMP() - INTERVAL 12 HOUR && `last` < UTC_TIMESTAMP() - INTERVAL 15 MINUTE ) OR ( `last` < UTC_TIMESTAMP() - INTERVAL 1 HOUR ))");
	}
	if(! $r){
		return;
	}

	if(! $queue_id)
		call_hooks('queue_predeliver', $a, $r);


	// delivery loop

	require_once('include/salmon.php');
	require_once('include/diaspora.php');

	foreach($r as $q_item) {

		// queue_predeliver hooks may have changed the queue db details, 
		// so check again if this entry still needs processing

		if($queue_id) {
			$qi = q("select * from queue where `id` = %d limit 1",
				intval($queue_id)
			);
		}
		else {
			$qi = q("SELECT * FROM `queue` WHERE `id` = %d AND `last` < UTC_TIMESTAMP() - INTERVAL 15 MINUTE ",
				intval($q_item['id'])
			);
		}
		if(! count($qi))
			continue;


		$c = q("SELECT * FROM `contact` WHERE `id` = %d LIMIT 1",
			intval($qi[0]['cid'])
		);
		if(! count($c)) {
			remove_queue_item($q_item['id']);
			continue;
		}
		if(in_array($c[0]['notify'],$deadguys)) {
				logger('queue: skipping known dead url: ' . $c[0]['notify']);
				update_queue_time($q_item['id']);
				continue;
		}

		$u = q("SELECT `user`.*, `user`.`pubkey` AS `upubkey`, `user`.`prvkey` AS `uprvkey` 
			FROM `user` WHERE `uid` = %d LIMIT 1",
			intval($c[0]['uid'])
		);
		if(! count($u)) {
			remove_queue_item($q_item['id']);
			continue;
		}

		$data      = $qi[0]['content'];
		$public    = $qi[0]['batch'];
		$contact   = $c[0];
		$owner     = $u[0];

		$deliver_status = 0;

		switch($contact['network']) {
			case NETWORK_DFRN:
				logger('queue: dfrndelivery: item ' . $q_item['id'] . ' for ' . $contact['name']);
				$deliver_status = dfrn_deliver($owner,$contact,$data);

				if($deliver_status == (-1)) {
					update_queue_time($q_item['id']);
					$deadguys[] = $contact['notify'];
				}
				else {
					remove_queue_item($q_item['id']);
				}
				break;
			case NETWORK_OSTATUS:
				if($contact['notify']) {
					logger('queue: slapdelivery: item ' . $q_item['id'] . ' for ' . $contact['name']);
					$deliver_status = slapper($owner,$contact['notify'],$data);

					if($deliver_status == (-1))
						update_queue_time($q_item['id']);
					else
						remove_queue_item($q_item['id']);
				}
				break;
			case NETWORK_DIASPORA:
				if($contact['notify']) {
					logger('queue: diaspora_delivery: item ' . $q_item['id'] . ' for ' . $contact['name']);
					$deliver_status = diaspora_transmit($owner,$contact,$data,$public,true);

					if($deliver_status == (-1))
						update_queue_time($q_item['id']);
					else
						remove_queue_item($q_item['id']);
				}
				break;

			default:
				$params = array('owner' => $owner, 'contact' => $contact, 'queue' => $q_item, 'result' => false);
				call_hooks('queue_deliver', $a, $params);
		
				if($params['result'])
						remove_queue_item($q_item['id']);
				else
						update_queue_time($q_item['id']);
	
				break;

		}
	}
		
	return;

}

if (array_search(__file__,get_included_files())===0){
  queue_run($argv,$argc);
  killme();
}
