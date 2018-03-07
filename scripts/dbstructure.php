#!/usr/bin/env php
<?php
/**
 * @file scripts/dbstructure.php
 * @brief Does database updates from the command line
 */

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Database\DBStructure;

require_once "boot.php";
require_once "include/dba.php";

$a = new App(dirname(__DIR__));
BaseObject::setApp($a);

@include ".htconfig.php";
dba::connect($db_host, $db_user, $db_pass, $db_data);
unset($db_host, $db_user, $db_pass, $db_data);

if ($_SERVER["argc"] == 2) {
	switch ($_SERVER["argv"][1]) {
		case "dryrun":
			DBStructure::update(true, false);
			return;
		case "update":
			DBStructure::update(true, true);

			$build = Config::get('system','build');
			if (!x($build)) {
				Config::set('system', 'build', DB_UPDATE_VERSION);
				$build = DB_UPDATE_VERSION;
			}

			$stored = intval($build);
			$current = intval(DB_UPDATE_VERSION);

			// run any left update_nnnn functions in update.php
			for ($x = $stored; $x < $current; $x ++) {
				$r = run_update_function($x);
				if (!$r) {
					break;
				}
			}

			Config::set('system','build',DB_UPDATE_VERSION);
			return;
		case "dumpsql":
			DBStructure::printStructure();
			return;
		case "toinnodb":
			DBStructure::convertToInnoDB();
			return;
	}
}

// print help
echo $_SERVER["argv"][0]." <command>\n";
echo "\n";
echo "Commands:\n";
echo "dryrun		show database update schema queries without running them\n";
echo "update		update database schema\n";
echo "dumpsql		dump database schema\n";
echo "toinnodb	convert all tables from MyISAM to InnoDB\n";
killme();

