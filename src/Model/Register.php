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

use Friendica\Content\Pager;
use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

/**
 * Class interacting with the register database table
 */
class Register
{
	/**
	 * Return the list of pending registrations
	 *
	 * @param int    $start Start count (Default is 0)
	 * @param int $count Count of the items per page (Default is @see Pager::ITEMS_PER_PAGE)
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function getPending($start = 0, $count = Pager::ITEMS_PER_PAGE)
	{
		$stmt = DBA::p(
			"SELECT `register`.*, `contact`.`name`, `contact`.`url`, `contact`.`micro`, `user`.`email`, `contact`.`nick`
			FROM `register`
			INNER JOIN `contact` ON `register`.`uid` = `contact`.`uid`
			INNER JOIN `user` ON `register`.`uid` = `user`.`uid`
			LIMIT ?, ?", $start, $count
		);

		return DBA::toArray($stmt);
	}

	/**
	 * Returns the pending user based on a given user id
	 *
	 * @param int $uid The user id
	 *
	 * @return array The pending user information
	 *
	 * @throws \Exception
	 */
	public static function getPendingForUser(int $uid)
	{
		return DBA::fetchFirst(
			"SELECT `register`.*, `contact`.`name`, `contact`.`url`, `contact`.`micro`, `user`.`email`
			FROM `register`
			INNER JOIN `contact` ON `register`.`uid` = `contact`.`uid`
			INNER JOIN `user` ON `register`.`uid` = `user`.`uid`
			WHERE `register`.uid = ?",
			$uid
		);
	}

	/**
	 * Returns the pending registration count
	 *
	 * @return int
	 * @throws \Exception
	 */
	public static function getPendingCount()
	{
		$register = DBA::fetchFirst(
			"SELECT COUNT(*) AS `count`
			FROM `register`
			INNER JOIN `contact` ON `register`.`uid` = `contact`.`uid` AND `contact`.`self`"
		);

		return $register['count'];
	}

	/**
	 * Returns the register record associated with the provided hash
	 *
	 * @param  string $hash
	 * @return array
	 * @throws \Exception
	 */
	public static function getByHash($hash)
	{
		return DBA::selectFirst('register', [], ['hash' => $hash]);
	}

	/**
	 * Returns true if a register record exists with the provided hash
	 *
	 * @param  string $hash
	 * @return boolean
	 * @throws \Exception
	 */
	public static function existsByHash($hash)
	{
		return DBA::exists('register', ['hash' => $hash]);
	}

	/**
	 * Creates a register record for an invitation and returns the auto-generated code for it
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function createForInvitation()
	{
		$code = Strings::getRandomName(8) . random_int(1000, 9999);

		$fields = [
			'hash' => $code,
			'created' => DateTimeFormat::utcNow()
		];

		DBA::insert('register', $fields);

		return $code;
	}

	/**
	 * Creates a register record for approval and returns the success of the database insert
	 * Checks for the existence of the provided user id
	 *
	 * @param  integer $uid      The ID of the user needing approval
	 * @param  string  $language The registration language
	 * @param  string  $note     An additional message from the user
	 * @return boolean
	 * @throws \Exception
	 */
	public static function createForApproval($uid, $language, $note = '')
	{
		$hash = Strings::getRandomHex();

		if (!User::exists($uid)) {
			return false;
		}

		$fields = [
			'hash'     => $hash,
			'created'  => DateTimeFormat::utcNow(),
			'uid'      => $uid,
			'password' => '', // Obsolete, slated for deletion
			'language' => $language,
			'note'     => $note
		];

		return DBA::insert('register', $fields);
	}

	/**
	 * Deletes a register record by the provided hash and returns the success of the database deletion
	 *
	 * @param  string $hash
	 * @return boolean
	 * @throws \Exception
	 */
	public static function deleteByHash($hash)
	{
		return DBA::delete('register', ['hash' => $hash]);
	}
}
