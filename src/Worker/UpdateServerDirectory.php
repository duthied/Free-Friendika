<?php
/**
 * @file src/Worker/UpdateServerDirectory.php
 */
namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Model\GServer;

class UpdateServerDirectory
{
	/**
	 * Query the given server for their users
	 * @param string $gserver Server URL
	 */
	public static function execute($gserver)
	{
		GServer::updateDirectory($gserver);
		return;
	}
}
