<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\Network;

/**
 * Check the git repository VERSION file and save the version to the DB
 *
 * Checking the upstream version is optional (opt-in) and can be done to either
 * the stable or the develop branch in the repository.
 */
class CheckVersion
{
	public static function execute()
	{
		Logger::log('checkversion: start');

		$checkurl = DI::config()->get('system', 'check_new_version_url', 'none');

		switch ($checkurl) {
			case 'master':
			case 'stable':
				$checked_url = 'https://raw.githubusercontent.com/friendica/friendica/stable/VERSION';
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

		DI::config()->set('system', 'git_friendica_version', $gitversion);

		Logger::log('checkversion: end');

		return;
	}
}
