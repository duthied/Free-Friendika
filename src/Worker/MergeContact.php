<?php

/**
 * @file src/Worker/MergeContact.php
 */

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Database\DBA;

class MergeContact
{
	public static function execute($first, $dup_id, $uid)
	{
		if (empty($first) || empty($dup_id) || ($first == $dup_id)) {
			// Invalid request
			return;
		}

		Logger::info('Handling duplicate', ['search' => $dup_id, 'replace' => $first]);

		// Search and replace
		DBA::update('item', ['contact-id' => $first], ['contact-id' => $dup_id]);
		DBA::update('thread', ['contact-id' => $first], ['contact-id' => $dup_id]);
		DBA::update('mail', ['contact-id' => $first], ['contact-id' => $dup_id]);
		DBA::update('photo', ['contact-id' => $first], ['contact-id' => $dup_id]);
		DBA::update('event', ['cid' => $first], ['cid' => $dup_id]);
		if ($uid == 0) {
			DBA::update('item', ['author-id' => $first], ['author-id' => $dup_id]);
			DBA::update('item', ['owner-id' => $first], ['owner-id' => $dup_id]);
			DBA::update('thread', ['author-id' => $first], ['author-id' => $dup_id]);
			DBA::update('thread', ['owner-id' => $first], ['owner-id' => $dup_id]);
		} else {
			/// @todo Check if some other data needs to be adjusted as well, possibly the "rel" status?
		}

		// Remove the duplicate
		DBA::delete('contact', ['id' => $dup_id]);
	}
}
