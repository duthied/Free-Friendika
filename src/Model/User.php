<?php

/**
 * @file src/Model/User.php
 * @brief This file includes the User class with user related database functions
 */

namespace Friendica\Model;

use Friendica\Core\Config;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Photo;
use Friendica\Object\Image;
use dba;

require_once 'boot.php';
require_once 'include/crypto.php';
require_once 'include/enotify.php';
require_once 'include/group.php';
require_once 'include/network.php';
require_once 'library/openid.php';
require_once 'include/pgettext.php';
require_once 'include/plugin.php';
require_once 'include/text.php';
/**
 * @brief This class handles User related functions
 */
class User
{
	/**
	 * @brief Authenticate a user with a clear text password
	 *
	 * User info can be any of the following:
	 * - User DB object
	 * - User Id
	 * - User email or username or nickname
	 * - User array with at least the uid and the hashed password
	 *
	 * @param mixed $user_info
	 * @param string $password
	 * @return boolean
	 */
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
	 * @brief Catch-all user creation function
	 *
	 * Creates a user from the provided data array, either form fields or OpenID.
	 * Required: { username, nickname, email } or { openid_url }
	 *
	 * Performs the following:
	 * - Sends to the OpenId auth URL (if relevant)
	 * - Creates new key pairs for crypto
	 * - Create self-contact
	 * - Create profile image
	 *
	 * @param array $data
	 * @return string
	 */
	public static function create(array $data)
	{
		$a = get_app();
		$result = array('success' => false, 'user' => null, 'password' => '', 'message' => '');

		$using_invites = Config::get('system', 'invitation_only');
		$num_invites   = Config::get('system', 'number_invites');

		$invite_id  = x($data, 'invite_id')  ? notags(trim($data['invite_id']))  : '';
		$username   = x($data, 'username')   ? notags(trim($data['username']))   : '';
		$nickname   = x($data, 'nickname')   ? notags(trim($data['nickname']))   : '';
		$email      = x($data, 'email')      ? notags(trim($data['email']))      : '';
		$openid_url = x($data, 'openid_url') ? notags(trim($data['openid_url'])) : '';
		$photo      = x($data, 'photo')      ? notags(trim($data['photo']))      : '';
		$password   = x($data, 'password')   ? trim($data['password'])           : '';
		$password1  = x($data, 'password1')  ? trim($data['password1'])          : '';
		$confirm    = x($data, 'confirm')    ? trim($data['confirm'])            : '';
		$blocked    = x($data, 'blocked')    ? intval($data['blocked'])          : 0;
		$verified   = x($data, 'verified')   ? intval($data['verified'])         : 0;

		$publish = x($data, 'profile_publish_reg') && intval($data['profile_publish_reg']) ? 1 : 0;
		$netpublish = strlen(Config::get('system', 'directory')) ? $publish : 0;

		if ($password1 != $confirm) {
			$result['message'] .= t('Passwords do not match. Password unchanged.') . EOL;
			return $result;
		} elseif ($password1 != "") {
			$password = $password1;
		}

		$tmp_str = $openid_url;

		if ($using_invites) {
			if (!$invite_id) {
				$result['message'] .= t('An invitation is required.') . EOL;
				return $result;
			}
			$r = q("SELECT * FROM `register` WHERE `hash` = '%s' LIMIT 1", dbesc($invite_id));
			if (!results($r)) {
				$result['message'] .= t('Invitation could not be verified.') . EOL;
				return $result;
			}
		}

		if (!x($username) || !x($email) || !x($nickname)) {
			if ($openid_url) {
				if (!validate_url($tmp_str)) {
					$result['message'] .= t('Invalid OpenID url') . EOL;
					return $result;
				}
				$_SESSION['register'] = 1;
				$_SESSION['openid'] = $openid_url;

				$openid = new LightOpenID;
				$openid->identity = $openid_url;
				$openid->returnUrl = System::baseUrl() . '/openid';
				$openid->required = array('namePerson/friendly', 'contact/email', 'namePerson');
				$openid->optional = array('namePerson/first', 'media/image/aspect11', 'media/image/default');
				try {
					$authurl = $openid->authUrl();
				} catch (Exception $e) {
					$result['message'] .= t("We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.") . EOL . EOL . t("The error message was:") . $e->getMessage() . EOL;
					return $result;
				}
				goaway($authurl);
				// NOTREACHED
			}

			notice(t('Please enter the required information.') . EOL);
			return;
		}

		if (!validate_url($tmp_str)) {
			$openid_url = '';
		}

		$err = '';

		// collapse multiple spaces in name
		$username = preg_replace('/ +/', ' ', $username);

		if (mb_strlen($username) > 48) {
			$result['message'] .= t('Please use a shorter name.') . EOL;
		}
		if (mb_strlen($username) < 3) {
			$result['message'] .= t('Name too short.') . EOL;
		}

		// So now we are just looking for a space in the full name.

		$loose_reg = Config::get('system', 'no_regfullname');
		if (!$loose_reg) {
			$username = mb_convert_case($username, MB_CASE_TITLE, 'UTF-8');
			if (!strpos($username, ' ')) {
				$result['message'] .= t("That doesn't appear to be your full \x28First Last\x29 name.") . EOL;
			}
		}


		if (!allowed_email($email)) {
			$result['message'] .= t('Your email domain is not among those allowed on this site.') . EOL;
		}

		if (!valid_email($email) || !validate_email($email)) {
			$result['message'] .= t('Not a valid email address.') . EOL;
		}

		// Disallow somebody creating an account using openid that uses the admin email address,
		// since openid bypasses email verification. We'll allow it if there is not yet an admin account.

		$adminlist = explode(",", str_replace(" ", "", strtolower($a->config['admin_email'])));

		//if((x($a->config,'admin_email')) && (strcasecmp($email,$a->config['admin_email']) == 0) && strlen($openid_url)) {
		if (x($a->config, 'admin_email') && in_array(strtolower($email), $adminlist) && strlen($openid_url)) {
			$r = q("SELECT * FROM `user` WHERE `email` = '%s' LIMIT 1",
				dbesc($email)
			);
			if (DBM::is_result($r)) {
				$result['message'] .= t('Cannot use that email.') . EOL;
			}
		}

		$nickname = $data['nickname'] = strtolower($nickname);

		if (!preg_match("/^[a-z0-9][a-z0-9\_]*$/", $nickname)) {
			$result['message'] .= t('Your "nickname" can only contain "a-z", "0-9" and "_".') . EOL;
		}

		$r = q("SELECT `uid` FROM `user`
			WHERE `nickname` = '%s' LIMIT 1",
			dbesc($nickname)
		);
		if (DBM::is_result($r)) {
			$result['message'] .= t('Nickname is already registered. Please choose another.') . EOL;
		}

		// Check deleted accounts that had this nickname. Doesn't matter to us,
		// but could be a security issue for federated platforms.

		$r = q("SELECT * FROM `userd`
			WHERE `username` = '%s' LIMIT 1",
			dbesc($nickname)
		);
		if (DBM::is_result($r)) {
			$result['message'] .= t('Nickname was once registered here and may not be re-used. Please choose another.') . EOL;
		}

		if (strlen($result['message'])) {
			return $result;
		}

		$new_password = strlen($password) ? $password : autoname(6) . mt_rand(100, 9999);
		$new_password_encoded = hash('whirlpool', $new_password);

		$result['password'] = $new_password;

		$keys = new_keypair(4096);

		if ($keys === false) {
			$result['message'] .= t('SERIOUS ERROR: Generation of security keys failed.') . EOL;
			return $result;
		}

		$prvkey = $keys['prvkey'];
		$pubkey = $keys['pubkey'];

		// Create another keypair for signing/verifying salmon protocol messages.
		$sres = new_keypair(512);
		$sprvkey = $sres['prvkey'];
		$spubkey = $sres['pubkey'];

		$r = q("INSERT INTO `user` (`guid`, `username`, `password`, `email`, `openid`, `nickname`,
			`pubkey`, `prvkey`, `spubkey`, `sprvkey`, `register_date`, `verified`, `blocked`, `timezone`, `default-location`)
			VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, 'UTC', '')",
			dbesc(generate_user_guid()),
			dbesc($username),
			dbesc($new_password_encoded),
			dbesc($email),
			dbesc($openid_url),
			dbesc($nickname),
			dbesc($pubkey),
			dbesc($prvkey),
			dbesc($spubkey),
			dbesc($sprvkey),
			dbesc(datetime_convert()),
			intval($verified),
			intval($blocked)
		);

		if ($r) {
			$r = q("SELECT * FROM `user`
				WHERE `username` = '%s' AND `password` = '%s' LIMIT 1",
				dbesc($username),
				dbesc($new_password_encoded)
			);
			if (DBM::is_result($r)) {
				$u = $r[0];
				$newuid = intval($r[0]['uid']);
			}
		} else {
			$result['message'] .= t('An error occurred during registration. Please try again.') . EOL;
			return $result;
		}

		/**
		 * if somebody clicked submit twice very quickly, they could end up with two accounts
		 * due to race condition. Remove this one.
		 */
		$r = q("SELECT `uid` FROM `user`
			WHERE `nickname` = '%s' ",
			dbesc($nickname)
		);
		if (DBM::is_result($r) && count($r) > 1 && $newuid) {
			$result['message'] .= t('Nickname is already registered. Please choose another.') . EOL;
			dba::delete('user', array('uid' => $newuid));
			return $result;
		}

		if (x($newuid) !== false) {
			$r = q("INSERT INTO `profile` ( `uid`, `profile-name`, `is-default`, `name`, `photo`, `thumb`, `publish`, `net-publish` )
				VALUES ( %d, '%s', %d, '%s', '%s', '%s', %d, %d ) ",
				intval($newuid),
				t('default'),
				1,
				dbesc($username),
				dbesc(System::baseUrl() . "/photo/profile/{$newuid}.jpg"),
				dbesc(System::baseUrl() . "/photo/avatar/{$newuid}.jpg"),
				intval($publish),
				intval($netpublish)
			);
			if ($r === false) {
				$result['message'] .= t('An error occurred creating your default profile. Please try again.') . EOL;
				// Start fresh next time.
				dba::delete('user', array('uid' => $newuid));
				return $result;
			}

			// Create the self contact
			Contact::createSelfFromUserId($newuid);

			// Create a group with no members. This allows somebody to use it
			// right away as a default group for new contacts.

			group_add($newuid, t('Friends'));

			$r = q("SELECT `id` FROM `group` WHERE `uid` = %d AND `name` = '%s'",
				intval($newuid),
				dbesc(t('Friends'))
			);
			if (DBM::is_result($r)) {
				$def_gid = $r[0]['id'];

				q("UPDATE `user` SET `def_gid` = %d WHERE `uid` = %d",
					intval($r[0]['id']),
					intval($newuid)
				);
			}

			if (Config::get('system', 'newuser_private') && $def_gid) {
				q("UPDATE `user` SET `allow_gid` = '%s' WHERE `uid` = %d",
					dbesc("<" . $def_gid . ">"),
					intval($newuid)
				);
			}
		}

		// if we have no OpenID photo try to look up an avatar
		if (!strlen($photo)) {
			$photo = avatar_img($email);
		}

		// unless there is no avatar-plugin loaded
		if (strlen($photo)) {
			$photo_failure = false;

			$filename = basename($photo);
			$img_str = fetch_url($photo, true);
			// guess mimetype from headers or filename
			$type = Image::guessType($photo, true);


			$Image = new Image($img_str, $type);
			if ($Image->isValid()) {
				$Image->scaleToSquare(175);

				$hash = photo_new_resource();

				$r = Photo::store($Image, $newuid, 0, $hash, $filename, t('Profile Photos'), 4);

				if ($r === false) {
					$photo_failure = true;
				}

				$Image->scaleDown(80);

				$r = Photo::store($Image, $newuid, 0, $hash, $filename, t('Profile Photos'), 5);

				if ($r === false) {
					$photo_failure = true;
				}

				$Image->scaleDown(48);

				$r = Photo::store($Image, $newuid, 0, $hash, $filename, t('Profile Photos'), 6);

				if ($r === false) {
					$photo_failure = true;
				}

				if (!$photo_failure) {
					q("UPDATE `photo` SET `profile` = 1 WHERE `resource-id` = '%s' ",
						dbesc($hash)
					);
				}
			}
		}

		call_hooks('register_account', $newuid);

		$result['success'] = true;
		$result['user'] = $u;
		return $result;
	}

	/**
	 * @brief Sends pending registration confiŕmation email
	 *
	 * @param string $email
	 * @param string $sitename
	 * @param string $username
	 * @return NULL|boolean from notification() and email() inherited
	 */
	public static function sendRegisterPendingEmail($email, $sitename, $username)
	{
		$body = deindent(t('
			Dear %1$s,
				Thank you for registering at %2$s. Your account is pending for approval by the administrator.
		'));

		$body = sprintf($body, $username, $sitename);

		return notification(array(
			'type' => SYSTEM_EMAIL,
			'to_email' => $email,
			'subject'=> sprintf( t('Registration at %s'), $sitename),
			'body' => $body));
	}

	/**
	 * @brief Sends registration confirmation
	 *
	 * It's here as a function because the mail is sent from different parts
	 *
	 * @param string $email
	 * @param string $sitename
	 * @param string $siteurl
	 * @param string $username
	 * @param string $password
	 * @return NULL|boolean from notification() and email() inherited
	 */
	public static function sendRegisterOpenEmail($email, $sitename, $siteurl, $username, $password)
	{
		$preamble = deindent(t('
			Dear %1$s,
				Thank you for registering at %2$s. Your account has been created.
		'));
		$body = deindent(t('
			The login details are as follows:
				Site Location:	%3$s
				Login Name:	%1$s
				Password:	%5$s

			You may change your password from your account "Settings" page after logging
			in.

			Please take a few moments to review the other account settings on that page.

			You may also wish to add some basic information to your default profile
			(on the "Profiles" page) so that other people can easily find you.

			We recommend setting your full name, adding a profile photo,
			adding some profile "keywords" (very useful in making new friends) - and
			perhaps what country you live in; if you do not wish to be more specific
			than that.

			We fully respect your right to privacy, and none of these items are necessary.
			If you are new and do not know anybody here, they may help
			you to make some new and interesting friends.


			Thank you and welcome to %2$s.'));

		$preamble = sprintf($preamble, $username, $sitename);
		$body = sprintf($body, $email, $sitename, $siteurl, $username, $password);

		return notification(array(
			'type' => SYSTEM_EMAIL,
			'to_email' => $email,
			'subject'=> sprintf( t('Registration details for %s'), $sitename),
			'preamble'=> $preamble,
			'body' => $body));
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
		dba::update('user', ['account_removed' => true, 'account_expires_on' => datetime_convert()], ['uid' => $uid]);
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
