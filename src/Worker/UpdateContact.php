<?php

/**
 * @file src/Worker/UpdateContact.php
 */

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Model\Contact;

class UpdateContact
{
	public static function execute($contact_id)
	{
		$success = Contact::updateFromProbe($contact_id);
		Logger::info('Updated from probe', ['id' => $contact_id, 'success' => $success]);
	}
}
