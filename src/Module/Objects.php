<?php
/**
 * @file src/Module/Objects.php
 */
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Protocol\ActivityPub;
use Friendica\Core\System;
use Friendica\Model\Item;
use Friendica\Database\DBA;
use Friendica\Util\HTTPSignature;

/**
 * ActivityPub Objects
 */
class Objects extends BaseModule
{
	public static function rawContent()
	{
		$a = self::getApp();

		if (empty($a->argv[1])) {
			System::httpExit(404);
		}

		if (!ActivityPub::isRequest()) {
			$a->internalRedirect(str_replace('objects/', 'display/', $a->query_string));
		}

		/// @todo Add Authentication to enable fetching of non public content
		// $requester = HTTPSignature::getSigner('', $_SERVER);

		$item = Item::selectFirst(['id'], ['guid' => $a->argv[1], 'origin' => true, 'private' => false]);
		if (!DBA::isResult($item)) {
			System::httpExit(404);
		}

		$data = ActivityPub\Transmitter::createObjectFromItemID($item['id']);

		header('Content-Type: application/activity+json');
		echo json_encode($data);
		exit();
	}
}
