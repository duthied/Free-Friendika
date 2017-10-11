<?php /** @file */

use Friendica\App;
use Friendica\Core\Config;

require_once('boot.php');

// Everything we need to boot standalone 'background' processes

function cli_startup() {
	global $a;

	if (empty($a)) {
		$a = new App(dirname(__DIR__));
	}

	@include(".htconfig.php");
	require_once("dba.php");
	dba::connect($db_host, $db_user, $db_pass, $db_data);
	unset($db_host, $db_user, $db_pass, $db_data);

	require_once('include/session.php');

	Config::load();

	$a->set_baseurl(get_config('system','url'));

	load_hooks();
}
