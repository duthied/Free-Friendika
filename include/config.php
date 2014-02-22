<?php

/**
 *
 * Arbitrary configuration storage
 * Note:
 * Please do not store booleans - convert to 0/1 integer values
 * The get_?config() functions return boolean false for keys that are unset,
 * and this could lead to subtle bugs.
 *
 * There are a few places in the code (such as the admin panel) where boolean
 * configurations need to be fixed as of 10/08/2011.
 */


// retrieve a "family" of config variables from database to cached storage

if(! function_exists('load_config')) {
function load_config($family) {
	global $a;

	// To-Do: How to integrate APC here?

	$r = q("SELECT * FROM `config` WHERE `cat` = '%s'", dbesc($family));
	if(count($r)) {
		foreach($r as $rr) {
			$k = $rr['k'];
			if ($family === 'config') {
				$a->config[$k] = $rr['v'];
			} else {
				$a->config[$family][$k] = $rr['v'];
			}
		}
	} else if ($family != 'config') {
		// Negative caching
		$a->config[$family] = "!<unset>!";
	}
}}

// get a particular config variable given the family name
// and key. Returns false if not set.
// $instore is only used by the set_config function
// to determine if the key already exists in the DB
// If a key is found in the DB but doesn't exist in
// local config cache, pull it into the cache so we don't have
// to hit the DB again for this item.

if(! function_exists('get_config')) {
function get_config($family, $key, $instore = false) {

	global $a;

	if(! $instore) {
		// Looking if the whole family isn't set
		if(isset($a->config[$family])) {
			if($a->config[$family] === '!<unset>!') {
				return false;
			}
		}

		if(isset($a->config[$family][$key])) {
			if($a->config[$family][$key] === '!<unset>!') {
				return false;
			}
			return $a->config[$family][$key];
		}
	}

	// If APC is enabled then fetch the data from there
	if (function_exists("apc_fetch") AND function_exists("apc_exists"))
		if (apc_exists($family."|".$key)) {
			$val = apc_fetch($family."|".$key);
			$a->config[$family][$key] = $val;

			if ($val === '!<unset>!')
				return false;
			else
				return $val;
		}

	$ret = q("SELECT `v` FROM `config` WHERE `cat` = '%s' AND `k` = '%s' LIMIT 1",
		dbesc($family),
		dbesc($key)
	);
	if(count($ret)) {
		// manage array value
		$val = (preg_match("|^a:[0-9]+:{.*}$|s", $ret[0]['v'])?unserialize( $ret[0]['v']):$ret[0]['v']);
		$a->config[$family][$key] = $val;

		// If APC is enabled then store the data there
		if (function_exists("apc_store"))
			apc_store($family."|".$key, $val, 600);

		return $val;
	}
	else {
		$a->config[$family][$key] = '!<unset>!';

		// If APC is enabled then store the data there
		if (function_exists("apc_store"))
			apc_store($family."|".$key, '!<unset>!', 600);
	}
	return false;
}}

// Store a config value ($value) in the category ($family)
// under the key ($key)
// Return the value, or false if the database update failed

if(! function_exists('set_config')) {
function set_config($family,$key,$value) {
	global $a;

	// If $a->config[$family] has been previously set to '!<unset>!', then
	// $a->config[$family][$key] will evaluate to $a->config[$family][0], and
	// $a->config[$family][$key] = $value will be equivalent to
	// $a->config[$family][0] = $value[0] (this causes infuriating bugs),
	// so unset the family before assigning a value to a family's key
	if($a->config[$family] === '!<unset>!')
		unset($a->config[$family]);

	// manage array value
	$dbvalue = (is_array($value)?serialize($value):$value);
	$dbvalue = (is_bool($dbvalue) ? intval($dbvalue) : $dbvalue);
	if(get_config($family,$key,true) === false) {
		$a->config[$family][$key] = $value;
		$ret = q("INSERT INTO `config` ( `cat`, `k`, `v` ) VALUES ( '%s', '%s', '%s' ) ",
			dbesc($family),
			dbesc($key),
			dbesc($dbvalue)
		);
		if($ret)
			return $value;
		return $ret;
	}

	$ret = q("UPDATE `config` SET `v` = '%s' WHERE `cat` = '%s' AND `k` = '%s' LIMIT 1",
		dbesc($dbvalue),
		dbesc($family),
		dbesc($key)
	);

	$a->config[$family][$key] = $value;

	// If APC is enabled then store the data there
	if (function_exists("apc_store"))
		apc_store($family."|".$key, $value, 600);

	if($ret)
		return $value;
	return $ret;
}}


if(! function_exists('load_pconfig')) {
function load_pconfig($uid,$family) {
	global $a;
	$r = q("SELECT * FROM `pconfig` WHERE `cat` = '%s' AND `uid` = %d",
		dbesc($family),
		intval($uid)
	);
	if(count($r)) {
		foreach($r as $rr) {
			$k = $rr['k'];
			$a->config[$uid][$family][$k] = $rr['v'];
		}
	} else if ($family != 'config') {
		// Negative caching
		$a->config[$uid][$family] = "!<unset>!";
	}
}}



if(! function_exists('get_pconfig')) {
function get_pconfig($uid,$family, $key, $instore = false) {

	global $a;

	if(! $instore) {
		// Looking if the whole family isn't set
		if(isset($a->config[$uid][$family])) {
			if($a->config[$uid][$family] === '!<unset>!') {
				return false;
			}
		}

		if(isset($a->config[$uid][$family][$key])) {
			if($a->config[$uid][$family][$key] === '!<unset>!') {
				return false;
			}
			return $a->config[$uid][$family][$key];
		}
	}

	// If APC is enabled then fetch the data from there
	if (function_exists("apc_fetch") AND function_exists("apc_exists"))
		if (apc_exists($uid."|".$family."|".$key)) {
			$val = apc_fetch($uid."|".$family."|".$key);
			$a->config[$uid][$family][$key] = $val;

			if ($val === '!<unset>!')
				return false;
			else
				return $val;
		}


	$ret = q("SELECT `v` FROM `pconfig` WHERE `uid` = %d AND `cat` = '%s' AND `k` = '%s' LIMIT 1",
		intval($uid),
		dbesc($family),
		dbesc($key)
	);

	if(count($ret)) {
		$val = (preg_match("|^a:[0-9]+:{.*}$|s", $ret[0]['v'])?unserialize( $ret[0]['v']):$ret[0]['v']);
		$a->config[$uid][$family][$key] = $val;

		// If APC is enabled then store the data there
		if (function_exists("apc_store"))
			apc_store($uid."|".$family."|".$key, $val, 600);

		return $val;
	}
	else {
		$a->config[$uid][$family][$key] = '!<unset>!';

		// If APC is enabled then store the data there
		if (function_exists("apc_store"))
			apc_store($uid."|".$family."|".$key, '!<unset>!', 600);
	}
	return false;
}}

if(! function_exists('del_config')) {
function del_config($family,$key) {

	global $a;
	if(x($a->config[$family],$key))
		unset($a->config[$family][$key]);
	$ret = q("DELETE FROM `config` WHERE `cat` = '%s' AND `k` = '%s' LIMIT 1",
		dbesc($family),
		dbesc($key)
	);
	// If APC is enabled then store the data there
	if (function_exists("apc_delete"))
		apc_delete($family."|".$key);

	return $ret;
}}



// Same as above functions except these are for personal config storage and take an
// additional $uid argument.

if(! function_exists('set_pconfig')) {
function set_pconfig($uid,$family,$key,$value) {

	global $a;

	// manage array value
	$dbvalue = (is_array($value)?serialize($value):$value);

	if(get_pconfig($uid,$family,$key,true) === false) {
		$a->config[$uid][$family][$key] = $value;
		$ret = q("INSERT INTO `pconfig` ( `uid`, `cat`, `k`, `v` ) VALUES ( %d, '%s', '%s', '%s' ) ",
			intval($uid),
			dbesc($family),
			dbesc($key),
			dbesc($dbvalue)
		);
		if($ret) 
			return $value;
		return $ret;
	}
	$ret = q("UPDATE `pconfig` SET `v` = '%s' WHERE `uid` = %d AND `cat` = '%s' AND `k` = '%s' LIMIT 1",
		dbesc($dbvalue),
		intval($uid),
		dbesc($family),
		dbesc($key)
	);

	$a->config[$uid][$family][$key] = $value;

	// If APC is enabled then store the data there
	if (function_exists("apc_store"))
		apc_store($uid."|".$family."|".$key, $value, 600);


	if($ret)
		return $value;
	return $ret;
}}

if(! function_exists('del_pconfig')) {
function del_pconfig($uid,$family,$key) {

	global $a;
	if(x($a->config[$uid][$family],$key))
		unset($a->config[$uid][$family][$key]);
	$ret = q("DELETE FROM `pconfig` WHERE `uid` = %d AND `cat` = '%s' AND `k` = '%s' LIMIT 1",
		intval($uid),
		dbesc($family),
		dbesc($key)
	);
	return $ret;
}}
