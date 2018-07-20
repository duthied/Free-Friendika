<?php

use Friendica\Database\dba;

function dbesc($str) {
	if (dba::$connected) {
		return(dba::escape($str));
	} else {
		return(str_replace("'","\\'",$str));
	}
}

/**
 * @brief execute SQL query with printf style args - deprecated
 *
 * Please use the dba:: functions instead:
 * dba::select, dba::exists, dba::insert
 * dba::delete, dba::update, dba::p, dba::e
 *
 * @param $args Query parameters (1 to N parameters of different types)
 * @return array|bool Query array
 */
function q($sql) {
	$args = func_get_args();
	unset($args[0]);

	if (!dba::$connected) {
		return false;
	}

	$sql = dba::clean_query($sql);
	$sql = dba::any_value_fallback($sql);

	$stmt = @vsprintf($sql, $args);

	$ret = dba::p($stmt);

	if (is_bool($ret)) {
		return $ret;
	}

	$columns = dba::columnCount($ret);

	$data = dba::inArray($ret);

	if ((count($data) == 0) && ($columns == 0)) {
		return true;
	}

	return $data;
}

function dba_timer() {
	return microtime(true);
}
