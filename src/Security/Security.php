<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Security;

use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\User;
use Friendica\Core\Session;

/**
 * Secures that User is allow to do requests
 */
class Security
{
	public static function canWriteToUserWall($owner)
	{
		static $verified = 0;

		if (!Session::isAuthenticated()) {
			return false;
		}

		$uid = local_user();
		if ($uid == $owner) {
			return true;
		}

		if (local_user() && ($owner == 0)) {
			return true;
		}

		if (!empty(Session::getRemoteContactID($owner))) {
			// use remembered decision and avoid a DB lookup for each and every display item
			// DO NOT use this function if there are going to be multiple owners
			// We have a contact-id for an authenticated remote user, this block determines if the contact
			// belongs to this page owner, and has the necessary permissions to post content

			if ($verified === 2) {
				return true;
			} elseif ($verified === 1) {
				return false;
			} else {
				$cid = Session::getRemoteContactID($owner);
				if (!$cid) {
					return false;
				}

				$r = q("SELECT `contact`.*, `user`.`page-flags` FROM `contact` INNER JOIN `user` on `user`.`uid` = `contact`.`uid`
					WHERE `contact`.`uid` = %d AND `contact`.`id` = %d AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
					AND `user`.`blockwall` = 0 AND `readonly` = 0  AND (`contact`.`rel` IN (%d , %d) OR `user`.`page-flags` = %d) LIMIT 1",
					intval($owner),
					intval($cid),
					intval(Contact::SHARING),
					intval(Contact::FRIEND),
					intval(User::PAGE_FLAGS_COMMUNITY)
				);

				if (DBA::isResult($r)) {
					$verified = 2;
					return true;
				} else {
					$verified = 1;
				}
			}
		}

		return false;
	}

	/**
	 * Create a permission string for an element based on the visitor
	 *
	 * @param integer $owner_id   User ID of the owner of the element
	 * @param boolean $accessible Should the element be accessible anyway?
	 * @return string SQL permissions
	 */
	public static function getPermissionsSQLByUserId(int $owner_id, bool $accessible = false)
	{
		$local_user = local_user();
		$remote_contact = Session::getRemoteContactID($owner_id);
		$acc_sql = '';

		if ($accessible) {
			$acc_sql = ' OR `accessible`';
		}

		/*
		 * Construct permissions
		 *
		 * default permissions - anonymous user
		 */
		$sql = " AND (allow_cid = ''
			 AND allow_gid = ''
			 AND deny_cid  = ''
			 AND deny_gid  = ''" . $acc_sql . ") ";

		/*
		 * Profile owner - everything is visible
		 */
		if ($local_user && $local_user == $owner_id) {
			$sql = '';
		/*
		 * Authenticated visitor. Load the groups the visitor belongs to.
		 */
		} elseif ($remote_contact) {
			$gs = '<<>>'; // should be impossible to match

			$groups = Group::getIdsByContactId($remote_contact);

			if (is_array($groups)) {
				foreach ($groups as $g) {
					$gs .= '|<' . intval($g) . '>';
				}
			}

			$sql = sprintf(
				" AND (NOT (deny_cid REGEXP '<%d>' OR deny_gid REGEXP '%s')
				  AND (allow_cid REGEXP '<%d>' OR allow_gid REGEXP '%s'
				  OR (allow_cid = '' AND allow_gid = ''))" . $acc_sql . ") ",
				intval($remote_contact),
				DBA::escape($gs),
				intval($remote_contact),
				DBA::escape($gs)
			);
		}
		return $sql;
	}
}
