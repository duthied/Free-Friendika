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
	public static function execute($contact_id)
	{
		$success = Contact::updateFromProbe($contact_id);
		// Update the "updated" field if the contact could be probed.
		// We don't do this in the function above, since we don't want to
		// update the contact whenever that function is called from anywhere.
		if ($success) {
			DBA::update('contact', ['updated' => DateTimeFormat::utcNow()], ['id' => $contact_id]);
		}

		Logger::info('Updated from probe', ['id' => $contact_id, 'success' => $success]);
	}
}
