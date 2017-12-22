<?php

use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Model\Photo;
use Friendica\Object\Image;

/**
 *
 * update.php - automatic system update
 *
 * This function is responsible for doing post update changes to the data
 * (not the structure) in the database.
 *
 * Database structure changes are done in src/Database/DBStructure.php
 *
 * If there is a need for a post process to a structure change, update this file
 * by adding a new function at the end with the number of the current DB_UPDATE_VERSION.
 *
 * The DB_UPDATE_VERSION will always be at least one greater than the last
 * numbered script in this file.
 *
 * Example:
 * You are currently on version 4711 and you are preparing changes that demand an update script.
 *
 * - Create a function "update_4711()" here in the update.php
 * - Apply the needed structural changes in src/Database/DBStructure.php
 * - Set DB_UPDATE_VERSION in boot.php to 4712.
 */

function update_1177() {
	require_once 'mod/profiles.php';

	$profiles = q("SELECT `uid`, `about`, `locality`, `pub_keywords`, `gender` FROM `profile` WHERE `is-default`");

	foreach ($profiles AS $profile) {
		if ($profile["about"].$profile["locality"].$profile["pub_keywords"].$profile["gender"] == "")
			continue;

		$profile["pub_keywords"] = profile_clean_keywords($profile["pub_keywords"]);

		$r = q("UPDATE `contact` SET `about` = '%s', `location` = '%s', `keywords` = '%s', `gender` = '%s' WHERE `self` AND `uid` = %d",
				dbesc($profile["about"]),
				dbesc($profile["locality"]),
				dbesc($profile["pub_keywords"]),
				dbesc($profile["gender"]),
				intval($profile["uid"])
			);
	}
}

function update_1178() {
	if (Config::get('system','no_community_page'))
		Config::set('system','community_page_style', CP_NO_COMMUNITY_PAGE);

	// Update the central item storage with uid=0
	Worker::add(PRIORITY_LOW, "threadupdate");

	return UPDATE_SUCCESS;
}

function update_1180() {

	// Fill the new fields in the term table.
	Worker::add(PRIORITY_LOW, "TagUpdate");

	return UPDATE_SUCCESS;
}

function update_1188() {

	if (strlen(Config::get('system','directory_submit_url')) &&
		!strlen(Config::get('system','directory'))) {
		Config::set('system','directory', dirname(Config::get('system','directory_submit_url')));
		Config::delete('system','directory_submit_url');
	}

	return UPDATE_SUCCESS;
}

function update_1190() {

	require_once 'include/plugin.php';

	Config::set('system', 'maintenance', 1);

	if (plugin_enabled('forumlist')) {
		$plugin = 'forumlist';
		$plugins = Config::get('system','addon');
		$plugins_arr = array();

		if ($plugins) {
			$plugins_arr = explode(",",str_replace(" ", "",$plugins));

			$idx = array_search($plugin, $plugins_arr);
			if ($idx !== false){
				unset($plugins_arr[$idx]);
				//delete forumlist manually from addon and hook table
				// since uninstall_plugin() don't work here
				q("DELETE FROM `addon` WHERE `name` = 'forumlist' ");
				q("DELETE FROM `hook` WHERE `file` = 'addon/forumlist/forumlist.php' ");
				Config::set('system','addon', implode(", ",$plugins_arr));
			}
		}
	}

	// select old formlist addon entries
	$r = q("SELECT `uid`, `cat`, `k`, `v` FROM `pconfig` WHERE `cat` = '%s' ",
		dbesc('forumlist')
	);

	// convert old forumlist addon entries in new config entries
	if (DBM::is_result($r)) {
		foreach ($r as $rr) {
			$uid = $rr['uid'];
			$family = $rr['cat'];
			$key = $rr['k'];
			$value = $rr['v'];

			if ($key === 'randomise')
				PConfig::delete($uid,$family,$key);

			if ($key === 'show_on_profile') {
				if ($value)
					PConfig::set($uid,feature,forumlist_profile,$value);

				PConfig::delete($uid,$family,$key);
			}

			if ($key === 'show_on_network') {
				if ($value)
					PConfig::set($uid,feature,forumlist_widget,$value);

				PConfig::delete($uid,$family,$key);
			}
		}
	}

	Config::set('system', 'maintenance', 0);

	return UPDATE_SUCCESS;

}

function update_1202() {
	$r = q("UPDATE `user` SET `account-type` = %d WHERE `page-flags` IN (%d, %d)",
		dbesc(ACCOUNT_TYPE_COMMUNITY), dbesc(PAGE_COMMUNITY), dbesc(PAGE_PRVGROUP));
}
