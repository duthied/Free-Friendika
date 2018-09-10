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

/**
 * ActivityPub Inbox
 */
class Inbox extends BaseModule
{
	public static function init()
	{
		$a = self::getApp();
		logger('Blubb: init 1');

		$postdata = file_get_contents('php://input');

		$obj = json_decode($postdata);

		if (empty($obj)) {
			exit();
		}

		$tempfile = tempnam(get_temppath(), 'activitypub');
		file_put_contents($tempfile, json_encode(['header' => $_SERVER, 'body' => $obj]));

		logger('Blubb: init ' . $tempfile);
		exit();
//		goaway($dest);
	}

	public static function post()
	{
		$a = self::getApp();

		logger('Blubb: post');
		exit();
//		goaway($dest);
	}
}
