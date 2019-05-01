<?php
/**
 * @file src/Module/Followers.php
 */
namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\Model\User;
use Friendica\Protocol\ActivityPub;

/**
 * ActivityPub Followers
 */
class Followers extends BaseModule
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

		$followers = ActivityPub\Transmitter::getFollowers($owner, $page);

		header('Content-Type: application/activity+json');
		echo json_encode($followers);
		exit();
	}
}
