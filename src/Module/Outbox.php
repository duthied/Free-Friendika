<?php
/**
 * @file src/Module/Outbox.php
 */
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Protocol\ActivityPub;
use Friendica\Core\System;
use Friendica\Model\User;
use Friendica\Util\HTTPSignature;

/**
 * ActivityPub Outbox
 */
class Outbox extends BaseModule
{
	public static function rawContent()
	{
		$a = self::getApp();

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
