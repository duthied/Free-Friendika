<?php
/*
Documentation: http://nodeinfo.diaspora.software/schema.html
*/

function nodeinfo_wellknown(&$a) {
	if (!get_config("system", "nodeinfo")) {
		http_status_exit(404);
		killme();
	}
	$nodeinfo = array("links" => array("rel" => "http://nodeinfo.diaspora.software/ns/schema/1.0",
					"href" => $a->get_baseurl()."/nodeinfo/1.0"));

	header('Content-type: application/json; charset=utf-8');
	echo json_encode($nodeinfo, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	exit;
}

function nodeinfo_init(&$a){
	if (!get_config("system", "nodeinfo")) {
		http_status_exit(404);
		killme();
	}

	if (($a->argc != 2) OR ($a->argv[1] != "1.0")) {
		http_status_exit(404);
		killme();
	}

	$smtp = (function_exists("imap_open") AND !get_config("system","imap_disabled") AND !get_config("system","dfrn_only"));

	$nodeinfo = array();
	$nodeinfo["version"] = "1.0";
	$nodeinfo["software"] = array("name" => "friendica", "version" => FRIENDICA_VERSION."-".DB_UPDATE_VERSION);

	$nodeinfo["protocols"] = array();
	$nodeinfo["protocols"]["inbound"] = array();
	$nodeinfo["protocols"]["outbound"] = array();

	if (get_config("system","diaspora_enabled")) {
		$nodeinfo["protocols"]["inbound"][] = "diaspora";
		$nodeinfo["protocols"]["outbound"][] = "diaspora";
	}

	$nodeinfo["protocols"]["inbound"][] = "friendica";
	$nodeinfo["protocols"]["outbound"][] = "friendica";

	if (!get_config("system","ostatus_disabled")) {
		$nodeinfo["protocols"]["inbound"][] = "gnusocial";
		$nodeinfo["protocols"]["outbound"][] = "gnusocial";
	}

	//if ($smtp) {
	//	$nodeinfo["protocols"]["inbound"][] = "smtp";
	//	$nodeinfo["protocols"]["outbound"][] = "smtp";
	//}


	$nodeinfo["services"] = array();

	if (nodeinfo_plugin_enabled("appnet") OR nodeinfo_plugin_enabled("buffer"))
		$nodeinfo["services"][] = "appnet";

	if (nodeinfo_plugin_enabled("blogger"))
		$nodeinfo["services"][] = "blogger";

	//if (get_config("system","diaspora_enabled"))
	//	$nodeinfo["services"][] = "diaspora";

	if (nodeinfo_plugin_enabled("dwpost"))
		$nodeinfo["services"][] = "dreamwidth";

	if (nodeinfo_plugin_enabled("fbpost") OR nodeinfo_plugin_enabled("buffer"))
		$nodeinfo["services"][] = "facebook";

	//$nodeinfo["services"][] = "friendica";

	//if (nodeinfo_plugin_enabled("statusnet") OR !get_config("system","ostatus_disabled"))
	if (nodeinfo_plugin_enabled("statusnet"))
		$nodeinfo["services"][] = "gnusocial";

	if (nodeinfo_plugin_enabled("gpluspost") OR nodeinfo_plugin_enabled("buffer"))
		$nodeinfo["services"][] = "google";

	if (nodeinfo_plugin_enabled("ijpost"))
		$nodeinfo["services"][] = "insanejournal";

	if (nodeinfo_plugin_enabled("libertree"))
		$nodeinfo["services"][] = "libertree";

	if (nodeinfo_plugin_enabled("buffer"))
		$nodeinfo["services"][] = "linkedin";

	if (nodeinfo_plugin_enabled("ljpost"))
		$nodeinfo["services"][] = "livejournal";

	if (nodeinfo_plugin_enabled("buffer"))
		$nodeinfo["services"][] = "pinterest";

	if (nodeinfo_plugin_enabled("posterous"))
		$nodeinfo["services"][] = "posterous";

	if (nodeinfo_plugin_enabled("pumpio"))
		$nodeinfo["services"][] = "pumpio";

	// redmatrix

	if ($smtp)
		$nodeinfo["services"][] = "smtp";

	if (nodeinfo_plugin_enabled("tumblr"))
		$nodeinfo["services"][] = "tumblr";

	if (nodeinfo_plugin_enabled("twitter"))
		$nodeinfo["services"][] = "twitter";

	if (nodeinfo_plugin_enabled("wppost"))
		$nodeinfo["services"][] = "wordpress";

	$nodeinfo["openRegistrations"] = ($a->config['register_policy'] != 0);

	$nodeinfo["usage"] = array();
	$nodeinfo["usage"]["users"] = array("total" => (int)get_config("nodeinfo","total_users"),
				"activeHalfyear" => (int)get_config("nodeinfo","active_users_halfyear"),
				"activeMonth" => (int)get_config("nodeinfo","active_users_monthly"));
	$nodeinfo["usage"]["localPosts"] = (int)get_config("nodeinfo","local_posts");
	$nodeinfo["usage"]["localComments"] = (int)get_config("nodeinfo","local_comments");

	//$nodeinfo["metadata"] = new stdClass();
	$nodeinfo["metadata"] = array("nodeName" => $a->config["sitename"]);

	header('Content-type: application/json; charset=utf-8');
	echo json_encode($nodeinfo, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	exit;
}

function nodeinfo_plugin_enabled($plugin) {
	$r = q("SELECT * FROM `addon` WHERE `installed` = 1 AND `name` = '%s'", $plugin);
	return((bool)(count($r) > 0));
}

function nodeinfo_cron() {

	$a = get_app();

	if (!get_config("system", "nodeinfo"))
		return;

	$last = get_config('nodeinfo','last_calucation');

	if($last) {
		// Calculate every 24 hours
		$next = $last + (24 * 60 * 60);
		if($next > time()) {
			logger("calculation intervall not reached");
			return;
		}
	}
        logger("cron_start");

	$users = q("SELECT profile.*, `user`.`login_date`, `lastitem`.`lastitem_date`
			FROM (SELECT MAX(`item`.`changed`) as `lastitem_date`, `item`.`uid`
				FROM `item`
					WHERE `item`.`type` = 'wall'
						GROUP BY `item`.`uid`) AS `lastitem`
						RIGHT OUTER JOIN `user` ON `user`.`uid` = `lastitem`.`uid`, `contact`, `profile`
                                WHERE
					`user`.`uid` = `contact`.`uid` AND `profile`.`uid` = `user`.`uid`
					AND `profile`.`is-default` AND (`profile`.`publish` OR `profile`.`net-publish`)
					AND `user`.`verified` AND `contact`.`self`
					AND NOT `user`.`blocked`
					AND NOT `user`.`account_removed`
					AND NOT `user`.`account_expired`");

	if (is_array($users)) {
			$total_users = count($users);
			$active_users_halfyear = 0;
			$active_users_monthly = 0;

			$halfyear = time() - (180 * 24 * 60 * 60);
			$month = time() - (30 * 24 * 60 * 60);

			foreach ($users AS $user) {
				if ((strtotime($user['login_date']) > $halfyear) OR
					(strtotime($user['lastitem_date']) > $halfyear))
					++$active_users_halfyear;

				if ((strtotime($user['login_date']) > $month) OR
					(strtotime($user['lastitem_date']) > $month))
					++$active_users_monthly;

			}
			set_config('nodeinfo','total_users', $total_users);
		        logger("total_users: ".$total_users, LOGGER_DEBUG);

			set_config('nodeinfo','active_users_halfyear', $active_users_halfyear);
			set_config('nodeinfo','active_users_monthly', $active_users_monthly);
	}

	//$posts = q("SELECT COUNT(*) AS local_posts FROM `item` WHERE `wall` AND `uid` != 0 AND `id` = `parent` AND left(body, 6) != '[share'");
	$posts = q("SELECT COUNT(*) AS `local_posts` FROM `item`
			INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			WHERE `contact`.`self` and `item`.`id` = `item`.`parent` AND left(body, 6) != '[share' AND `item`.`network` IN ('%s', '%s', '%s')",
			dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DIASPORA), dbesc(NETWORK_DFRN));

	if (!is_array($posts))
		$local_posts = -1;
	else
		$local_posts = $posts[0]["local_posts"];

	set_config('nodeinfo','local_posts', $local_posts);

        logger("local_posts: ".$local_posts, LOGGER_DEBUG);

	$posts = q("SELECT COUNT(*) AS `local_comments` FROM `item`
			INNER JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
			WHERE `contact`.`self` and `item`.`id` != `item`.`parent` AND `item`.`network` IN ('%s', '%s', '%s')",
			dbesc(NETWORK_OSTATUS), dbesc(NETWORK_DIASPORA), dbesc(NETWORK_DFRN));

	if (!is_array($posts))
		$local_comments = -1;
	else
		$local_comments = $posts[0]["local_comments"];

	set_config('nodeinfo','local_comments', $local_comments);

	// Now trying to register
	$url = "http://the-federation.info/register/".$a->get_hostname();
        logger('registering url: '.$url, LOGGER_DEBUG);
	$ret = fetch_url($url);
        logger('registering answer: '.$ret, LOGGER_DEBUG);

        logger("cron_end");
	set_config('nodeinfo','last_calucation', time());
}

?>
