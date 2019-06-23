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

		// Update the update date fields only when we are forcing the update
		if (!$force) {
			return;
		}

		// Update the "last-update", "success_update" and "failure_update" field only when it is a public contact.
		// These fields are set in OnePoll for all non public contacts.
		$updated = DateTimeFormat::utcNow();
		if ($success) {
			DBA::update('contact', ['last-update' => $updated, 'success_update' => $updated], ['id' => $contact_id, 'uid' => 0]);
		} else {
			DBA::update('contact', ['last-update' => $updated, 'failure_update' => $updated], ['id' => $contact_id, 'uid' => 0]);
		}
	}
}
