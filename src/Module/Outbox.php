<?php
/**
 * @file src/Module/Outbox.php
 */
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\Model\User;
use Friendica\Protocol\ActivityPub;

/**
 * ActivityPub Outbox
 */
class Outbox extends BaseModule
{
	public static function rawContent()
	{
		$a = self::getApp();

		// @TODO: Replace with parameter from router
		if (empty($a->argv[1])) {
			System::httpExit(404);
		}

		$owner = User::getOwnerDataByNick($a->argv[1]);
		if (empty($owner)) {
			System::httpExit(404);
		}

		$page = defaults($_REQUEST, 'page', null);

		/// @todo Add Authentication to enable fetching of non public content
		// $requester = HTTPSignature::getSigner('', $_SERVER);

		$outbox = ActivityPub\Transmitter::getOutbox($owner, $page);

		header('Content-Type: application/activity+json');
		echo json_encode($outbox);
		exit();
	}
}
