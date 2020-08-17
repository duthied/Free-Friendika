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
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\Strings;

class UpdateServerPeers
{
	/**
	 * Query the given server for their known peers
	 * @param string $gserver Server URL
	 */
	public static function execute(string $url)
	{
		$ret = DI::httpRequest()->get($url . '/api/v1/instance/peers');
		if (!$ret->isSuccess() || empty($ret->getBody())) {
			Logger::info('Server is not reachable or does not offer the "peers" endpoint', ['url' => $url]);
			return;
		}

		$peers = json_decode($ret->getBody());
		if (empty($peers) || !is_array($peers)) {
			Logger::info('Server does not have any peers listed', ['url' => $url]);
			return;
		}

		Logger::info('Server peer update start', ['url' => $url]);

		$total = 0;
		$added = 0;
		foreach ($peers as $peer) {
			++$total;
			if (DBA::exists('gserver', ['nurl' => Strings::normaliseLink('http://' . $peer)])) {
				// We already know this server
				continue;
			}
			// This endpoint doesn't offer the schema. So we assume that it is HTTPS.
			Worker::add(PRIORITY_LOW, 'UpdateGServer', 'https://' . $peer);
			++$added;
		}
		Logger::info('Server peer update ended', ['total' => $total, 'added' => $added, 'url' => $url]);
	}

	/**
	 * Fetch server list from remote servers and adds them when they are new.
	 *
	 * @param string $poco URL to the POCO endpoint
	 */
	private static function fetchServerlist($poco)
	{
		$curlResult = DI::httpRequest()->get($poco . '/@server');
		if (!$curlResult->isSuccess()) {
			Logger::info('Server is not reachable or does not offer the "poco" endpoint', ['poco' => $poco]);
			return;
		}

		$serverlist = json_decode($curlResult->getBody(), true);
		if (!is_array($serverlist)) {
			Logger::info('Server does not have any servers listed', ['poco' => $poco]);
			return;
		}

		Logger::info('PoCo Server update start', ['poco' => $poco]);

		$total = 0;
		$added = 0;
		foreach ($serverlist as $server) {
			++$total;
			if (DBA::exists('gserver', ['nurl' => Strings::normaliseLink($server['url'])])) {
				// We already know this server
				continue;
			}
			// This endpoint doesn't offer the schema. So we assume that it is HTTPS.
			Worker::add(PRIORITY_LOW, 'UpdateGServer', $server['url']);
			++$added;
		}

		Logger::info('PoCo Server update ended', ['total' => $total, 'added' => $added, 'poco' => $poco]);
	}
}
