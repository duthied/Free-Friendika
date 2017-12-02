<?php
/**
 * @file mod/post.php
 * @brief Zot endpoint
 */

use Friendica\App;
use Friendica\Database\DBM;
use dba;

require_once 'include/crypto.php';
// not yet ready for prime time
//require_once('include/zot.php');

/**
 * @param object $a App
 * @return void
 */
function post_post(App $a)
{
	$bulk_delivery = false;

	if ($a->argc == 1) {
		$bulk_delivery = true;
	} else {
		$nickname = $a->argv[2];
		$r = dba::select('user', array(), array('nickname' => $nickname, 'account_expired' => 0, 'account_removed' => 0), array('limit' => 1));
		if (! DBM::is_result($r)) {
			http_status_exit(500);
		}

		$importer = $r;
	}

	$xml = file_get_contents('php://input');

	logger('mod-post: new zot: ' . $xml, LOGGER_DATA);

	if (! $xml) {
		http_status_exit(500);
	}

	$msg = zot_decode($importer, $xml);

	logger('mod-post: decoded msg: ' . print_r($msg, true), LOGGER_DATA);

	if (! is_array($msg)) {
		http_status_exit(500);
	}

	$ret = 0;
	$ret = zot_incoming($bulk_delivery, $importer, $msg);
	http_status_exit(($ret) ? $ret : 200);
	// NOTREACHED
}
