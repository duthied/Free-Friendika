<?php
/**
 * @file mod/receive.php
 * @brief Diaspora endpoint
 */

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Protocol\Diaspora;
use Friendica\Util\Network;

/**
 * @param App $a App
 * @return void
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function receive_post(App $a)
{
	$enabled = intval(Config::get('system', 'diaspora_enabled'));
	if (!$enabled) {
		Logger::log('mod-diaspora: disabled');
		throw new \Friendica\Network\HTTPException\InternalServerErrorException();
	}

	if (($a->argc == 2) && ($a->argv[1] === 'public')) {
		$public = true;
		$importer = [];
	} else {
		$public = false;

		if ($a->argc != 3 || $a->argv[1] !== 'users') {
			throw new \Friendica\Network\HTTPException\InternalServerErrorException();
		}
		$guid = $a->argv[2];

		$importer = DBA::selectFirst('user', [], ['guid' => $guid, 'account_expired' => false, 'account_removed' => false]);
		if (!DBA::isResult($importer)) {
			throw new \Friendica\Network\HTTPException\InternalServerErrorException();
		}
	}

	// It is an application/x-www-form-urlencoded

	Logger::log('mod-diaspora: receiving post', Logger::DEBUG);

	if (empty($_POST['xml'])) {
		$postdata = Network::postdata();
		if ($postdata == '') {
			throw new \Friendica\Network\HTTPException\InternalServerErrorException();
		}

		Logger::log('mod-diaspora: message is in the new format', Logger::DEBUG);
		$msg = Diaspora::decodeRaw($importer, $postdata);
	} else {
		$xml = urldecode($_POST['xml']);

		Logger::log('mod-diaspora: decode message in the old format', Logger::DEBUG);
		$msg = Diaspora::decode($importer, $xml);

		if ($public && !$msg) {
			Logger::log('mod-diaspora: decode message in the new format', Logger::DEBUG);
			$msg = Diaspora::decodeRaw($importer, $xml);
		}
	}

	Logger::log('mod-diaspora: decoded', Logger::DEBUG);

	Logger::log('mod-diaspora: decoded msg: ' . print_r($msg, true), Logger::DATA);

	if (!is_array($msg)) {
		throw new \Friendica\Network\HTTPException\InternalServerErrorException();
	}

	Logger::log('mod-diaspora: dispatching', Logger::DEBUG);

	$ret = true;
	if ($public) {
		Diaspora::dispatchPublic($msg);
	} else {
		$ret = Diaspora::dispatch($importer, $msg);
	}

	if ($ret) {
		throw new \Friendica\Network\HTTPException\OKException();
	} else {
		throw new \Friendica\Network\HTTPException\InternalServerErrorException();
	}
}
