<?php
/**
 * @file src/Worker/CreateShadowentry.php
 * @brief This script creates posts with UID = 0 for a given public post.
 *
 * This script is started from mod/item.php to save some time when doing a post.
 */

namespace Friendica\Worker;

require_once("include/threads.php");

class CreateShadowentry {
	public static function execute($message_id = 0) {
		if (empty($message_id)) {
			return;
		}

		add_shadow_entry($message_id);
	}
}
