<?php
/**
 * @file src/Worker/DBUpdate.php
 * @brief This file is called when the database structure needs to be updated
 */
namespace Friendica\Worker;

use Friendica\Core\Config;
use Friendica\Core\Update;

class DBUpdate
{
	public static function execute()
	{
		// We are deleting the latest dbupdate entry.
		// This is done to avoid endless loops because the update was interupted.
		Config::delete('database', 'dbupdate_'.DB_UPDATE_VERSION);

		Update::run();
	}
}
