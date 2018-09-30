<?php
/**
 * @file src/Worker/ProfileUpdate.php
 * @brief Send updated profile data to Diaspora and ActivityPub
 */

namespace Friendica\Worker;

use Friendica\Protocol\Diaspora;
use Friendica\Protocol\ActivityPub;

class ProfileUpdate {
	public static function execute($uid = 0) {
		if (empty($uid)) {
			return;
		}

		ActivityPub::transmitProfileUpdate($uid);
		Diaspora::sendProfile($uid);
	}
}
