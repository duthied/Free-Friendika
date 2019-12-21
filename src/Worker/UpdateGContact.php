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
	/**
	 * Update global contact via probe
	 * @param string $url     Global contact url
	 * @param string $command
	 */
	public static function execute($url, $command = '')
	{
		$force = ($command == "force");

		$success = GContact::updateFromProbe($url, $force);

		Logger::info('Updated from probe', ['url' => $url, 'force' => $force, 'success' => $success]);
	}
}
