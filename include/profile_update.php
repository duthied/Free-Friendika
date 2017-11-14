<?php
use Friendica\Protocol\Diaspora;

function profile_update_run(&$argv, &$argc) {
	if ($argc != 2) {
		return;
	}

	$uid = intval($argv[1]);

	Diaspora::send_profile($uid);
}
