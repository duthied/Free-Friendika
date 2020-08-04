<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Model\Contact;

use Friendica\Database\DBA;
use Friendica\Model\Contact;

/**
 * This class provides information about user related contacts based on the "user-contact" table.
 */
class User
{
	/**
	 * Block contact id for user id
	 *
	 * @param int     $cid     Either public contact id or user's contact id
	 * @param int     $uid     User ID
	 * @param boolean $blocked Is the contact blocked or unblocked?
	 * @throws \Exception
	 */
	public static function setBlocked($cid, $uid, $blocked)
	{
		$cdata = Contact::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		if ($cdata['user'] != 0) {
			DBA::update('contact', ['blocked' => $blocked], ['id' => $cdata['user'], 'pending' => false]);
		}

		DBA::update('user-contact', ['blocked' => $blocked], ['cid' => $cdata['public'], 'uid' => $uid], true);
	}

	/**
	 * Returns "block" state for contact id and user id
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 *
	 * @return boolean is the contact id blocked for the given user?
	 * @throws \Exception
	 */
	public static function isBlocked($cid, $uid)
	{
		$cdata = Contact::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		$public_blocked = false;

		if (!empty($cdata['public'])) {
			$public_contact = DBA::selectFirst('user-contact', ['blocked'], ['cid' => $cdata['public'], 'uid' => $uid]);
			if (DBA::isResult($public_contact)) {
				$public_blocked = $public_contact['blocked'];
			}
		}

		$user_blocked = $public_blocked;

		if (!empty($cdata['user'])) {
			$user_contact = DBA::selectFirst('contact', ['blocked'], ['id' => $cdata['user'], 'pending' => false]);
			if (DBA::isResult($user_contact)) {
				$user_blocked = $user_contact['blocked'];
			}
		}

		if ($user_blocked != $public_blocked) {
			DBA::update('user-contact', ['blocked' => $user_blocked], ['cid' => $cdata['public'], 'uid' => $uid], true);
		}

		return $user_blocked;
	}

	/**
	 * Ignore contact id for user id
	 *
	 * @param int     $cid     Either public contact id or user's contact id
	 * @param int     $uid     User ID
	 * @param boolean $ignored Is the contact ignored or unignored?
	 * @throws \Exception
	 */
	public static function setIgnored($cid, $uid, $ignored)
	{
		$cdata = Contact::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		if ($cdata['user'] != 0) {
			DBA::update('contact', ['readonly' => $ignored], ['id' => $cdata['user'], 'pending' => false]);
		}

		DBA::update('user-contact', ['ignored' => $ignored], ['cid' => $cdata['public'], 'uid' => $uid], true);
	}

	/**
	 * Returns "ignore" state for contact id and user id
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 *
	 * @return boolean is the contact id ignored for the given user?
	 * @throws \Exception
	 */
	public static function isIgnored($cid, $uid)
	{
		$cdata = Contact::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		$public_ignored = false;

		if (!empty($cdata['public'])) {
			$public_contact = DBA::selectFirst('user-contact', ['ignored'], ['cid' => $cdata['public'], 'uid' => $uid]);
			if (DBA::isResult($public_contact)) {
				$public_ignored = $public_contact['ignored'];
			}
		}

		$user_ignored = $public_ignored;

		if (!empty($cdata['user'])) {
			$user_contact = DBA::selectFirst('contact', ['readonly'], ['id' => $cdata['user'], 'pending' => false]);
			if (DBA::isResult($user_contact)) {
				$user_ignored = $user_contact['readonly'];
			}
		}

		if ($user_ignored != $public_ignored) {
			DBA::update('user-contact', ['ignored' => $user_ignored], ['cid' => $cdata['public'], 'uid' => $uid], true);
		}

		return $user_ignored;
	}

	/**
	 * Set "collapsed" for contact id and user id
	 *
	 * @param int     $cid       Either public contact id or user's contact id
	 * @param int     $uid       User ID
	 * @param boolean $collapsed are the contact's posts collapsed or uncollapsed?
	 * @throws \Exception
	 */
	public static function setCollapsed($cid, $uid, $collapsed)
	{
		$cdata = Contact::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		DBA::update('user-contact', ['collapsed' => $collapsed], ['cid' => $cdata['public'], 'uid' => $uid], true);
	}

	/**
	 * Returns "collapsed" state for contact id and user id
	 *
	 * @param int $cid Either public contact id or user's contact id
	 * @param int $uid User ID
	 *
	 * @return boolean is the contact id blocked for the given user?
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isCollapsed($cid, $uid)
	{
		$cdata = Contact::getPublicAndUserContacID($cid, $uid);
		if (empty($cdata)) {
			return;
		}

		$collapsed = false;

		if (!empty($cdata['public'])) {
			$public_contact = DBA::selectFirst('user-contact', ['collapsed'], ['cid' => $cdata['public'], 'uid' => $uid]);
			if (DBA::isResult($public_contact)) {
				$collapsed = $public_contact['collapsed'];
			}
		}

		return $collapsed;
	}
}
