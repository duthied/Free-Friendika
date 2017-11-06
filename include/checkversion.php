<?php

/**
 * @file include/checkversion.php
 *
 * @brief save Friendica upstream version to the DB
 **/

use Friendica\Core\Config;

/**
 * @brief check the git repository VERSION file and save the version to the DB
 *
 * Checking the upstream version is optional (opt-in) and can be done to either
 * the master or the develop branch in the repository.
 */
function checkversion_run () {
	global $a;

	logger('checkversion: start');

	$checkurl = Config::get('system', 'check_new_version_url', 'none');

	// check for new versions at all?
	if ($checkurl == 'none' ) {
		return;
	}
	$gitversion = dbesc(trim(fetch_url($checkurl)));
	Config::set('system', 'git_friendica_version', $gitversion);

	logger('checkversion: end');

	return;
}
