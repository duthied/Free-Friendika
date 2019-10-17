<?php

/**
 * @file src/Worker/UpdateGcontact.php
 */

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Model\GContact;
use Friendica\Database\DBA;

class UpdateGContact
{
	public static function execute($url, $command = '')
	{
		$force = ($command == "force");

		$success = GContact::updateFromProbe($url, $force);

		Logger::info('Updated from probe', ['url' => $url, 'force' => $force, 'success' => $success]);
	}
}
