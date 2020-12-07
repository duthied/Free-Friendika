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
use Friendica\Model\GServer;
use Friendica\Util\Strings;

class UpdateGServer
{
	/**
	 * Update the given server
	 * @param string  $server_url    Server URL
	 * @param boolean $only_nodeinfo Only use nodeinfo for server detection
	 */
	public static function execute(string $server_url, bool $only_nodeinfo = false)
	{
		if (empty($server_url)) {
			return;
		}

		$server_url = filter_var($server_url, FILTER_SANITIZE_URL);
		if (substr(Strings::normaliseLink($server_url), 0, 7) != 'http://') {
			return;
		}

		$ret = GServer::check($server_url, '', false, $only_nodeinfo);
		Logger::info('Updated gserver', ['url' => $server_url, 'result' => $ret]);
	}
}
