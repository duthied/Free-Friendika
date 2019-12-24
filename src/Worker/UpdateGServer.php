<?php
/**
 * @file src/Worker/UpdateGServer.php
 */
namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Model\GServer;
use Friendica\Util\Strings;

class UpdateGServer
{
	/**
	 * Update the given server
	 * @param string $server_url Server URL
	 */
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
		Logger::info('Updated gserver', ['url' => $server_url, 'result' => $ret]);
	}
}
