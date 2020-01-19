<?php

/**
 * @file src/Worker/CheckVersion.php
 *
 * save Friendica upstream version to the DB
 **/
namespace Friendica\Worker;

use Friendica\Core\Config;
use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\Util\Network;

/**
 * check the git repository VERSION file and save the version to the DB
 *
 * Checking the upstream version is optional (opt-in) and can be done to either
 * the master or the develop branch in the repository.
 */
class CheckVersion
{
	public static function execute()
	{
		Logger::log('checkversion: start');

		$checkurl = Config::get('system', 'check_new_version_url', 'none');

		switch ($checkurl) {
			case 'master':
				$checked_url = 'https://raw.githubusercontent.com/friendica/friendica/master/VERSION';
				break;
			case 'develop':
				$checked_url = 'https://raw.githubusercontent.com/friendica/friendica/develop/VERSION';
				break;
			default:
				// don't check
				return;
		}
		Logger::log("Checking VERSION from: ".$checked_url, Logger::DEBUG);

		// fetch the VERSION file
		$gitversion = DBA::escape(trim(Network::fetchUrl($checked_url)));
		Logger::log("Upstream VERSION is: ".$gitversion, Logger::DEBUG);

		Config::set('system', 'git_friendica_version', $gitversion);

		Logger::log('checkversion: end');

		return;
	}
}
