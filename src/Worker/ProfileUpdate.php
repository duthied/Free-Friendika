<?php
/**
 * @file src/Worker/ProfileUpdate.php
 * @brief Send updated profile data to Diaspora
 */

namespace Friendica\Worker;

use Friendica\Protocol\Diaspora;

class ProfileUpdate {
	public static function execute($uid = 0) {
		if (empty($uid)) {
			return;
		}

		Diaspora::sendProfile($uid);
	}
}
