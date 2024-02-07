<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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
use Friendica\Protocol\ActivityPub\Queue;

class ProcessReplyByUri
{
	/**
	 * Process queued replies
	 *
	 * @param string $uri post url
	 *
	 * @return void
	 */
	public static function execute(string $uri)
	{
		Logger::info('Start processing queued replies', ['url' => $uri]);
		$count = Queue::processReplyByUri($uri);
		Logger::info('Successfully processed queued replies', ['count' => $count, 'url' => $uri]);
	}
}
