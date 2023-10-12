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

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\Model\Contact\Relation;
use Friendica\Model\Post;

/**
 * Update the interaction scores 
 */
class UpdateScores
{
	public static function execute($param = '', $hook_function = '')
	{
		Logger::notice('Start score update');

		$users = DBA::select('user', ['uid'], ["`verified` AND NOT `blocked` AND NOT `account_removed` AND NOT `account_expired` AND `uid` > ?", 0]);
		while ($user = DBA::fetch($users)) {
			Relation::calculateInteractionScore($user['uid']);
		}
		DBA::close($users);

		Logger::notice('Score update done');

		Post\Engagement::expire();

		return;
	}
}
