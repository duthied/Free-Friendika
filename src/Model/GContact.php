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

namespace Friendica\Model;

use Exception;
use Friendica\Database\DBA;

/**
 * This class handles GlobalContact related functions
 */
class GContact
{
	/**
	 * @param integer $uid id
	 * @param integer $cid id
	 * @return integer
	 * @throws Exception
	 */
	public static function countCommonFriends($uid, $cid)
	{
		$r = q(
			"SELECT count(*) as `total`
			FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
			WHERE `glink`.`cid` = %d AND `glink`.`uid` = %d AND
			NOT `gcontact`.`failed`
			AND `gcontact`.`nurl` IN (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0 and id != %d) ",
			intval($cid),
			intval($uid),
			intval($uid),
			intval($cid)
		);

		if (DBA::isResult($r)) {
			return $r[0]['total'];
		}
		return 0;
	}

	/**
	 * @param integer $uid  id
	 * @param integer $zcid zcid
	 * @return integer
	 * @throws Exception
	 */
	public static function countCommonFriendsZcid($uid, $zcid)
	{
		$r = q(
			"SELECT count(*) as `total`
			FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
			where `glink`.`zcid` = %d
			and `gcontact`.`nurl` in (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0) ",
			intval($zcid),
			intval($uid)
		);

		if (DBA::isResult($r)) {
			return $r[0]['total'];
		}

		return 0;
	}

	/**
	 * @param integer $uid     user
	 * @param integer $cid     cid
	 * @param integer $start   optional, default 0
	 * @param integer $limit   optional, default 9999
	 * @param boolean $shuffle optional, default false
	 * @return object
	 * @throws Exception
	 */
	public static function commonFriends($uid, $cid, $start = 0, $limit = 9999, $shuffle = false)
	{
		if ($shuffle) {
			$sql_extra = " order by rand() ";
		} else {
			$sql_extra = " order by `gcontact`.`name` asc ";
		}

		$r = q(
			"SELECT `gcontact`.*, `contact`.`id` AS `cid`
			FROM `glink`
			INNER JOIN `gcontact` ON `glink`.`gcid` = `gcontact`.`id`
			INNER JOIN `contact` ON `gcontact`.`nurl` = `contact`.`nurl`
			WHERE `glink`.`cid` = %d and `glink`.`uid` = %d
				AND `contact`.`uid` = %d AND `contact`.`self` = 0 AND `contact`.`blocked` = 0
				AND `contact`.`hidden` = 0 AND `contact`.`id` != %d
				AND NOT `gcontact`.`failed`
				$sql_extra LIMIT %d, %d",
			intval($cid),
			intval($uid),
			intval($uid),
			intval($cid),
			intval($start),
			intval($limit)
		);

		/// @TODO Check all calling-findings of this function if they properly use DBA::isResult()
		return $r;
	}

	/**
	 * @param integer $uid     user
	 * @param integer $zcid    zcid
	 * @param integer $start   optional, default 0
	 * @param integer $limit   optional, default 9999
	 * @param boolean $shuffle optional, default false
	 * @return object
	 * @throws Exception
	 */
	public static function commonFriendsZcid($uid, $zcid, $start = 0, $limit = 9999, $shuffle = false)
	{
		if ($shuffle) {
			$sql_extra = " order by rand() ";
		} else {
			$sql_extra = " order by `gcontact`.`name` asc ";
		}

		$r = q(
			"SELECT `gcontact`.*
			FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
			where `glink`.`zcid` = %d
			and `gcontact`.`nurl` in (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0)
			$sql_extra limit %d, %d",
			intval($zcid),
			intval($uid),
			intval($start),
			intval($limit)
		);

		/// @TODO Check all calling-findings of this function if they properly use DBA::isResult()
		return $r;
	}
}
