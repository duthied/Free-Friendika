<?php
/**
 * @file src/Model/User.php
 * @brief This file includes the User class with user related database functions
 */
namespace Friendica\Model;

use Friendica\Core\System;
use Friendica\Core\Worker;
use dba;

require_once 'boot.php';
require_once 'plugin.php';

/**
 * @brief This class handles User related functions
 */
class User
{
	/**
	 * @param object $uid user to remove
	 * @return void
	 */
	public static function remove($uid)
	{
		if (!$uid) {
			return;
		}

		logger('Removing user: ' . $uid);

		$r = dba::select('user', array(), array('uid' => $uid), array("limit" => 1));

		call_hooks('remove_user', $r);

		// save username (actually the nickname as it is guaranteed
		// unique), so it cannot be re-registered in the future.

		dba::insert('userd', array('username' => $r['nickname']));

		// The user and related data will be deleted in "cron_expire_and_remove_users" (cronjobs.php)
		q("UPDATE `user` SET `account_removed` = 1, `account_expires_on` = UTC_TIMESTAMP() WHERE `uid` = %d", intval($uid));
		Worker::add(PRIORITY_HIGH, "Notifier", "removeme", $uid);

		// Send an update to the directory
		Worker::add(PRIORITY_LOW, "Directory", $r['url']);

		if ($uid == local_user()) {
			unset($_SESSION['authenticated']);
			unset($_SESSION['uid']);
			goaway(System::baseUrl());
		}
	}
}
