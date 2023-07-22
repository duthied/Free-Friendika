<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

use Friendica\Content\Widget;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;

/**
 * This class provides information about contact circles based on the "group_member" table.
 */
class Circle
{
	/**
	 * Returns a list of contacts belonging in a circle
	 *
	 * @param int $gid
	 * @return array
	 * @throws \Exception
	 */
	public static function getById(int $gid): array
	{
		$return = [];

		if (intval($gid)) {
			$networks = Widget::unavailableNetworks();
			$sql_values = array_merge([$gid, DI::userSession()->getLocalUserId()], $networks);

			$stmt = DBA::p('SELECT `circle_member`.`contact-id`, `contact`.*
				FROM `contact`
				INNER JOIN `group_member` AS `circle_member`
					ON `contact`.`id` = `circle_member`.`contact-id`
				WHERE `gid` = ?
				AND `contact`.`uid` = ?
				AND NOT `contact`.`self`
				AND NOT `contact`.`deleted`
				AND NOT `contact`.`blocked`
				AND NOT `contact`.`pending`
				AND NOT `contact`.`network` IN (' . substr(str_repeat('?, ', count($networks)), 0, -2) . ')
				ORDER BY `contact`.`name` ASC',
				$sql_values
			);

			if (DBA::isResult($stmt)) {
				$return = DBA::toArray($stmt);
			}
		}

		return $return;
	}

	/**
	 * Returns uncircled contact count or list for user
	 *
	 * Returns either the total number of uncircled contacts for the given user
	 * id or a paginated list of uncircled contacts.
	 *
	 * @param int $uid uid
	 * @return array
	 * @throws \Exception
	 */
	public static function listUncircled(int $uid)
	{
		$networks = Widget::unavailableNetworks();
		$query = "`uid` = ? AND NOT `self` AND NOT `deleted` AND NOT `blocked` AND NOT `pending` AND NOT `failed`
			AND NOT `network` IN (" . substr(str_repeat('?, ', count($networks)), 0, -2) . ")
			AND `id` NOT IN (SELECT DISTINCT(`contact-id`) FROM `group_member` AS `circle_member` INNER JOIN `group` AS `circle` ON `circle`.`id` = `circle_member`.`gid`
			WHERE `circle`.`uid` = ? AND `contact-id` = `contact`.`id`)";
		$condition = array_merge([$query], [$uid], $networks, [$uid]);

		return Contact::selectToArray([], $condition);
	}

	/**
	 * Remove a contact from all circles
	 *
	 * @param integer $contact_id
	 *
	 * @return boolean Success
	 */
	public static function removeContact(int $contact_id)
	{
		return DBA::delete('group_member', ['contact-id' => $contact_id]);
	}
}
