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
	public static function rawContent()
	{
		$a = self::getApp();

		$postdata = file_get_contents('php://input');

		if (empty($postdata)) {
			System::httpExit(400);
		}

		if (!empty($a->argv[1])) {
			$user = DBA::selectFirst('user', ['uid'], ['nickname' => $a->argv[1]]);
			if (!DBA::isResult($user)) {
				System::httpExit(404);
			}
			$uid = $user['uid'];
		} else {
			$uid = 0;
		}

		ActivityPub\Receiver::processInbox($postdata, $_SERVER, $uid);

		System::httpExit(202);
	}
}
