<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Protocol\ActivityPub;

use Friendica\Core\Logger;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\DateTimeFormat;

/**
 * This class handles the page cache
 */
class PageCache
{
	/**
	 * Add content to the page cache
	 *
	 * @param string $page
	 * @param mixed $content
	 * @return void
	 */
	public static function add(string $page, $content)
	{
		if (!DI::config()->get('system', 'pagecache')) {
			return;
		}

		DBA::delete('pagecache', ["`fetched` < ?", DateTimeFormat::utc('now - 5 minutes')]);
		DBA::insert('pagecache', ['page' => $page, 'content' => serialize($content), 'fetched' => DateTimeFormat::utcNow()], Database::INSERT_UPDATE);

		Logger::debug('Page added', ['page' => $page]);
	}

	/**
	 * Fetch data from the page cache
	 *
	 * @param string $page
	 * @return mixed
	 */
	public static function fetch(string $page)
	{
		$pagecache = DBA::selectFirst('pagecache', [], ['page' => $page]);
		if (empty($pagecache['content'])) {
			return null;
		}

		DBA::update('pagecache', ['fetched' => DateTimeFormat::utcNow()], ['page' => $page]);

		Logger::debug('Page fetched', ['page' => $page]);

		return unserialize($pagecache['content']);
	}
}
