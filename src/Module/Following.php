<?php
/**
 * @file src/Module/Following.php
 */
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\Model\User;
use Friendica\Protocol\ActivityPub;

/**
 * ActivityPub Following
 */
class Following extends BaseModule
{
	public static function rawContent()
	{
		$a = self::getApp();

		// @TODO: Replace with parameter from router
		if (empty($a->argv[1])) {
			System::httpExit(404);
		}

		// @TODO: Replace with parameter from router
		$owner = User::getOwnerDataByNick($a->argv[1]);
		if (empty($owner)) {
			System::httpExit(404);
		}

		$page = defaults($_REQUEST, 'page', null);

		$Following = ActivityPub\Transmitter::getFollowing($owner, $page);

		header('Content-Type: application/activity+json');
		echo json_encode($Following);
		exit();
	}
}
