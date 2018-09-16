<?php
/**
 * @file src/Module/Inbox.php
 */
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Protocol\ActivityPub;
use Friendica\Core\System;
use Friendica\Database\DBA;

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

		$tempfile = tempnam(get_temppath(), $filename);
		file_put_contents($tempfile, json_encode(['argv' => $a->argv, 'header' => $_SERVER, 'body' => $postdata]));

		logger('Incoming message stored under ' . $tempfile);

		if (!empty($a->argv[1])) {
			$user = DBA::selectFirst('user', ['uid'], ['nickname' => $a->argv[1]]);
			if (!DBA::isResult($user)) {
				System::httpExit(404);
			}
			$uid = $user['uid'];
		} else {
			$uid = 0;
		}

		ActivityPub::processInbox($postdata, $_SERVER, $uid);

		System::httpExit(201);
	}
}
