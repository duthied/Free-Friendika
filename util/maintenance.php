<?php

use \Friendica\Core\Config;

require_once("boot.php");

$a = new App;
@include(".htconfig.php");

$lang = get_browser_language();
load_translation_table($lang);

require_once("include/dba.php");
$db = new dba($db_host, $db_user, $db_pass, $db_data, false);
unset($db_host, $db_user, $db_pass, $db_data);

Config::load();

$maint_mode = 1;
if($argc > 1) {
	$maint_mode = intval($argv[1]);
}

Config::set('system', 'maintenance', $maint_mode);

if($maint_mode AND ($argc > 2)) {
	$reason_arr = $argv;
	array_shift($reason_arr);
	array_shift($reason_arr);

	$reason = implode(' ', $reason_arr);
	Config::set('system', 'maintenance_reason', $reason);
} else {
	Config::set('system', 'maintenance_reason', '');
}

if($maint_mode) {
	$mode_str = "maintenance mode";
} else {
	$mode_str = "normal mode";
}

echo "\n\tSystem set in $mode_str\n";

if ($reason != '') {
	echo "\tMaintenance reason: $reason\n\n";
} else {
	echo "\n";
}

echo "Usage:\n\n";
echo "\tphp {$argv[0]} [1] [Maintenance reason]\tSet the system in maintenance mode\n";
echo "\tphp {$argv[0]} 0  \tSet the system in normal mode\n\n";
