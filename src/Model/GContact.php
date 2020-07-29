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
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;

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
		$sourceId = Contact::getPublicIdByUserId($uid);

		$targetIds = Contact::getPublicAndUserContacID($cid, $uid);

		$condition = [
			'NOT `self` AND NOT `blocked` AND NOT `hidden` AND `id` != ?',
			$sourceId,
		];

		return ContactRelation::countCommonFollows($sourceId, $targetIds['public'] ?? 0, $condition);
	}

	/**
	 * @param integer $uid  id
	 * @param integer $zcid zcid
	 * @return integer
	 * @throws Exception
	 */
	public static function countCommonFriendsZcid($uid, $zcid)
	{
		$sourceId = Contact::getPublicIdByUserId($uid);

		$targetPublicContact = DI::dba()->fetchFirst("
SELECT `id`
FROM `contact` c 
JOIN `gcontact` z ON z.`nurl` = c.`nurl`
AND z.`id` = ?
AND c.`uid` = 0
LIMIT 1",
			$zcid
		);

		return ContactRelation::countCommonFollowers($sourceId, $targetPublicContact['id'] ?? 0);
	}

	/**
	 * Returns the cross-section between the local user contacts and one of their contact's own relationships
	 * as known by the local node.
	 *
	 * @param integer $uid     local user id
	 * @param integer $cid     user contact id to compare friends with
	 * @param integer $start   optional, default 0
	 * @param integer $limit   optional, default 9999
	 * @param boolean $shuffle optional, default false
	 * @return array
	 * @throws Exception
	 */
	public static function commonFriends($uid, $cid, $start = 0, $limit = 9999, $shuffle = false)
	{
		$sourceId = Contact::getPublicIdByUserId($uid);

		$targetIds = Contact::getPublicAndUserContacID($cid, $uid);

		$condition = [
			'NOT `self` AND NOT `blocked` AND NOT `hidden` AND `id` != ?',
			$sourceId,
		];

		return ContactRelation::listCommonFollows($sourceId, $targetIds['public'] ?? 0, $condition, $limit, $start, $shuffle);
	}

	/**
	 * Returns the cross-section between a local user and a remote visitor contact's own relationships
	 * as known by the local node.
	 *
	 * @param integer $uid     local user id
	 * @param integer $zcid    remote visitor contact zcid
	 * @param integer $start   optional, default 0
	 * @param integer $limit   optional, default 9999
	 * @param boolean $shuffle optional, default false
	 * @return array
	 * @throws Exception
	 */
	public static function commonFriendsZcid($uid, $zcid, $start = 0, $limit = 9999, $shuffle = false)
	{
		$sourceId = Contact::getPublicIdByUserId($uid);

		$targetPublicContact = DI::dba()->fetchFirst("
SELECT c.`id`
FROM `contact` c 
JOIN `gcontact` z ON z.`nurl` = c.`nurl`
AND z.`id` = ?
AND c.`uid` = 0
LIMIT 1",
			$zcid
		);

		return ContactRelation::listCommonFollows($sourceId, $targetPublicContact['id'] ?? 0, [], $limit, $start, $shuffle);
	}

	/**
	 * @param integer $uid user
	 * @param integer $cid cid
	 * @return integer
	 * @throws Exception
	 */
	public static function countAllFriends($uid, $cid)
	{
		$cids = Contact::getPublicAndUserContacID($cid, $uid);

		return ContactRelation::countFollows($cids['public'] ?? 0);
	}

	/**
	 * @param integer $uid   user
	 * @param integer $cid   cid
	 * @param integer $start optional, default 0
	 * @param integer $limit optional, default 80
	 * @return array
	 * @throws Exception
	 */
	public static function allFriends($uid, $cid, $start = 0, $limit = 80)
	{
		$cids = Contact::getPublicAndUserContacID($cid, $uid);

		return ContactRelation::listFollows($cids['public'] ?? 0, [], $limit, $start);
	}
}
