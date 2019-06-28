<?php

/**
 * @file src/Worker/UpdateContact.php
 */

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Model\Contact;
use Friendica\Util\DateTimeFormat;
use Friendica\Database\DBA;

class UpdateContact
{
	public static function execute($contact_id, $command = '')
	{
		$force = ($command == "force");

		$success = Contact::updateFromProbe($contact_id, '', $force);

		Logger::info('Updated from probe', ['id' => $contact_id, 'force' => $force, 'success' => $success]);
	}
}
