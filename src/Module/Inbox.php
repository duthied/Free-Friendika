<?php
/**
 * @file src/Module/Inbox.php
 */
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Protocol\ActivityPub;
use Friendica\Core\System;

/**
 * ActivityPub Inbox
 */
class Inbox extends BaseModule
{
	public static function init()
	{
		$a = self::getApp();

		$postdata = file_get_contents('php://input');

		if (empty($postdata)) {
			System::httpExit(400);
		}

		if (ActivityPub::verifySignature($postdata, $_SERVER)) {
			$filename = 'signed-activitypub';
		} else {
			$filename = 'failed-activitypub';
		}

		$tempfile = tempnam(get_temppath(), filename);
		file_put_contents($tempfile, json_encode(['header' => $_SERVER, 'body' => $postdata]));

		System::httpExit(200);
	}
}
