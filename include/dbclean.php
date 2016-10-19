<?php
require_once("boot.php");

global $a, $db;

if(is_null($a))
	$a = new App;

if(is_null($db)) {
	@include(".htconfig.php");
	require_once("include/dba.php");
	$db = new dba($db_host, $db_user, $db_pass, $db_data);
	unset($db_host, $db_user, $db_pass, $db_data);
}

load_config('config');
load_config('system');

update_shadow_copy();
killme();

function remove_orphans() {

	logger("Deleting orphaned data from thread table");
	q("DELETE FROM `thread` WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`parent` = `thread`.`iid`)");

	logger("Deleting orphaned data from notify table");
	q("DELETE FROM `notify` WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`id` = `notify`.`iid`)");

	logger("Deleting orphaned data from sign table");
	q("DELETE FROM `sign` WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`id` = `sign`.`iid`)");

	logger("Deleting orphaned data from term table");
	q("DELETE FROM `term` WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`id` = `term`.`oid`)");

	logger("Done deleting orphaned data from tables");
}
?>
