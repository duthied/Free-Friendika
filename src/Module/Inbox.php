<?php
/**
 * @file src/Module/Inbox.php
 */
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Network;
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

		$tempfile = tempnam(get_temppath(), 'activitypub');
		file_put_contents($tempfile, json_encode(['header' => $_SERVER, 'body' => $postdata]));

		System::httpExit(200);
	}
}
