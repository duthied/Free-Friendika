<?php
/**
 * @file src/Worker/UpdateServerDirectory.php
 */
namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Model\GServer;

class UpdateServerDirectory
{
	public static function execute($gserver)
	{
		GServer::updateDirectory($gserver);
		return;
	}
}
