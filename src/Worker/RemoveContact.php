<?php
/**
 * @file src/Worker/RemoveContact.php
 * @brief Removes orphaned data from deleted contacts
 */
namespace Friendica\Worker;

use Friendica\Core\Config;
use dba;

require_once 'include/dba.php';

class RemoveContact {
	public static function execute($id) {

		// Only delete if the contact doesn't exist (anymore)
		$r = dba::exists('contact', array('id' => $id));
		if ($r) {
			return;
		}

		// Now we delete all the depending table entries
		dba::delete('contact', array('id' => $id));
	}
}
