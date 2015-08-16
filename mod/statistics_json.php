<?php
require_once("mod/nodeinfo.php");

function statistics_json_init(&$a) {

        if (!get_config("system", "nodeinfo")) {
                http_status_exit(404);
                killme();
        }

	$statistics = array(
			"name" => $a->config["sitename"],
			"network" => FRIENDICA_PLATFORM,
			"version" => FRIENDICA_VERSION."-".DB_UPDATE_VERSION,
			"registrations_open" => ($a->config['register_policy'] != 0),
			"total_users" => get_config('nodeinfo','total_users'),
			"active_users_halfyear" => get_config('nodeinfo','active_users_halfyear'),
			"active_users_monthly" => get_config('nodeinfo','active_users_monthly'),
			"local_posts" => get_config('nodeinfo','local_posts')
			);

	$statistics["services"] = array();
	$statistics["services"]["appnet"] = nodeinfo_plugin_enabled("appnet");
	$statistics["services"]["blogger"] = nodeinfo_plugin_enabled("blogger");
	$statistics["services"]["buffer"] = nodeinfo_plugin_enabled("buffer");
	$statistics["services"]["dreamwidth"] = nodeinfo_plugin_enabled("dwpost");
	$statistics["services"]["facebook"] = nodeinfo_plugin_enabled("fbpost");
	$statistics["services"]["gnusocial"] = nodeinfo_plugin_enabled("statusnet");
	$statistics["services"]["googleplus"] = nodeinfo_plugin_enabled("gpluspost");
	$statistics["services"]["libertree"] = nodeinfo_plugin_enabled("libertree");
	$statistics["services"]["livejournal"] = nodeinfo_plugin_enabled("ljpost");
	$statistics["services"]["pumpio"] = nodeinfo_plugin_enabled("pumpio");
	$statistics["services"]["twitter"] = nodeinfo_plugin_enabled("twitter");
	$statistics["services"]["tumblr"] = nodeinfo_plugin_enabled("tumblr");
	$statistics["services"]["wordpress"] = nodeinfo_plugin_enabled("wppost");

	$statistics["appnet"] = $statistics["services"]["appnet"];
	$statistics["blogger"] = $statistics["services"]["blogger"];
	$statistics["buffer"] = $statistics["services"]["buffer"];
	$statistics["dreamwidth"] = $statistics["services"]["dreamwidth"];
	$statistics["facebook"] = $statistics["services"]["facebook"];
	$statistics["gnusocial"] = $statistics["services"]["gnusocial"];
	$statistics["googleplus"] = $statistics["services"]["googleplus"];
	$statistics["libertree"] = $statistics["services"]["libertree"];
	$statistics["livejournal"] = $statistics["services"]["livejournal"];
	$statistics["pumpio"] = $statistics["services"]["pumpio"];
	$statistics["twitter"] = $statistics["services"]["twitter"];
	$statistics["tumblr"] = $statistics["services"]["tumblr"];
	$statistics["wordpress"] = $statistics["services"]["wordpress"];

	header("Content-Type: application/json");
	echo json_encode($statistics, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	logger("statistics_init: printed ".print_r($statistics, true), LOGGER_DATA);
	killme();
}
