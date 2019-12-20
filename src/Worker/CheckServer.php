<?php
/**
 * @file src/Worker/CheckServer.php
 */
namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Model\GServer;
use Friendica\Util\Strings;

class CheckServer
{
	// Searches for the poco server list.
	public static function execute($server_url)
	{
		if (empty($server_url)) {
			return;
		}

		$server_url = filter_var($server_url, FILTER_SANITIZE_URL);
		if (substr(Strings::normaliseLink($server_url), 0, 7) != 'http://') {
			return;
		}

		$ret = GServer::check($server_url);
		Logger::log('Checking server', ['url' => $server_url, 'result' => $ret]);
	}
}
