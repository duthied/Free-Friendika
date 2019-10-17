<?php
/**
 * @file src/Worker/DBUpdate.php
 * @brief This file is called when the database structure needs to be updated
 */
namespace Friendica\Worker;

use Friendica\BaseObject;
use Friendica\Core\Config;
use Friendica\Core\Update;

class DBUpdate extends BaseObject
{
	public static function execute()
	{
		// Just in case the last update wasn't failed
		if (Config::get('system', 'update', Update::SUCCESS, true) != Update::FAILED) {
			Update::run(self::getApp()->getBasePath());
		}
	}
}
