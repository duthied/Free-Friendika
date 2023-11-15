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

namespace Friendica\Model;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\DateTimeFormat;

/**
 * Model for DB specific logic for the search entity
 */
class Search
{
	/**
	 * Returns the list of user defined tags (e.g. #Friendica)
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function getUserTags(): array
	{
		$user_condition = ["`verified` AND NOT `blocked` AND NOT `account_removed` AND NOT `account_expired` AND `user`.`uid` > ?", 0];

		$abandon_days = intval(DI::config()->get('system', 'account_abandon_days'));
		if (!empty($abandon_days)) {
			$user_condition = DBA::mergeConditions($user_condition, ["`last-activity` > ?", DateTimeFormat::utc('now - ' . $abandon_days . ' days')]);
		}

		$condition = $user_condition;
		$condition[0] = "SELECT DISTINCT(`term`) FROM `search` INNER JOIN `user` ON `search`.`uid` = `user`.`uid` WHERE " . $user_condition[0];
		$sql = array_shift($condition);
		$termsStmt = DBA::p($sql, $condition);

		$tags = [];
		while ($term = DBA::fetch($termsStmt)) {
			$tags[] = trim(mb_strtolower($term['term']), '#');
		}
		DBA::close($termsStmt);

		$condition = $user_condition;
		$condition[0] = "SELECT `include-tags` FROM `channel` INNER JOIN `user` ON `channel`.`uid` = `user`.`uid` WHERE " . $user_condition[0];
		$sql = array_shift($condition);
		$channels = DBA::p($sql, $condition);
		while ($channel = DBA::fetch($channels)) {
			foreach (explode(',', $channel['include-tags']) as $tag) {
				$tag = trim(mb_strtolower($tag));
				if (empty($tag)) {
					continue;
				}
				if (!in_array($tag, $tags)) {
					$tags[]	= $tag;
				}
			}
		}
		DBA::close($channels);

		sort($tags);

		return $tags;
	}
}
