<?php

/**
 * @file src/Model/User.php
 * @brief This file includes the User class with user related database functions
 */

namespace Friendica\Model;

use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use dba;

require_once 'boot.php';
require_once 'include/plugin.php';

/**
 * @brief This class handles User related functions
 */
class User
{
	public static function authenticate($user_info, $password)
	{
		if (is_object($user_info)) {
			$user = (array) $user_info;
		} elseif (is_int($user_info)) {
			$user = dba::select('user',
				['uid', 'password'],
				[
					'uid' => $user_info,
					'blocked' => 0,
					'account_expired' => 0,
					'account_removed' => 0,
					'verified' => 1
				],
				['limit' => 1]
			);
		} elseif (is_string($user_info)) {
			$user = dba::fetch_first('SELECT `uid`, `password`
				FROM `user`
				WHERE (`email` = ? OR `username` = ? OR `nickname` = ?)
				AND `blocked` = 0
				AND `account_expired` = 0
				AND `account_removed` = 0
				AND `verified` = 1
				LIMIT 1',
				$user_info,
				$user_info,
				$user_info
			);
		} else {
			$user = $user_info;
		}

		if (!DBM::is_result($user) || !isset($user['uid']) || !isset($user['password'])) {
			return false;
		}

		$password_hashed = hash('whirlpool', $password);

		if ($password_hashed !== $user['password']) {
			return false;
		}

		return $user['uid'];
	}

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

		$user = dba::select('user', [], ['uid' => $uid], ['limit' => 1]);

		call_hooks('remove_user', $user);

		// save username (actually the nickname as it is guaranteed
		// unique), so it cannot be re-registered in the future.
		dba::insert('userd', ['username' => $user['nickname']]);

		// The user and related data will be deleted in "cron_expire_and_remove_users" (cronjobs.php)
		dba::update('user', ['account_removed' => 1, 'account_expires_on' => datetime_convert()], ['uid' => intval($uid)]);
		Worker::add(PRIORITY_HIGH, "Notifier", "removeme", $uid);

		// Send an update to the directory
		Worker::add(PRIORITY_LOW, "Directory", $user['url']);

		if ($uid == local_user()) {
			unset($_SESSION['authenticated']);
			unset($_SESSION['uid']);
			goaway(System::baseUrl());
		}
	}
}
