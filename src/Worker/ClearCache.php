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

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Photo;
use Friendica\Util\Proxy as ProxyUtils;

/**
 * Clear cache entries
 */
class ClearCache
{
	public static function execute()
	{
		$a = DI::app();
		$last = DI::config()->get('system', 'cache_last_cleared');

		if ($last) {
			$next = $last + (3600); // Once per hour
			$clear_cache = ($next <= time());
		} else {
			$clear_cache = true;
		}

		if (!$clear_cache) {
			return;
		}

		// clear old cache
		DI::cache()->clear();

		// clear old item cache files
		clear_cache();

		// clear cache for photos
		clear_cache($a->getBasePath(), $a->getBasePath() . "/photo");

		// clear smarty cache
		clear_cache($a->getBasePath() . "/view/smarty3/compiled", $a->getBasePath() . "/view/smarty3/compiled");

		// clear cache for image proxy
		if (!DI::config()->get("system", "proxy_disabled")) {
			clear_cache($a->getBasePath(), $a->getBasePath() . "/proxy");

			$cachetime = DI::config()->get('system', 'proxy_cache_time');

			if (!$cachetime) {
				$cachetime = ProxyUtils::DEFAULT_TIME;
			}

			$condition = ['`uid` = 0 AND `resource-id` LIKE "pic:%" AND `created` < NOW() - INTERVAL ? SECOND', $cachetime];
			Photo::delete($condition);
		}

		// Delete the cached OEmbed entries that are older than three month
		DBA::delete('oembed', ["`created` < NOW() - INTERVAL 3 MONTH"]);

		// Delete the cached "parse_url" entries that are older than three month
		DBA::delete('parsed_url', ["`created` < NOW() - INTERVAL 3 MONTH"]);

		if (DI::config()->get('system', 'optimize_tables')) {
			Logger::info('Optimize start');
			DBA::e("OPTIMIZE TABLE `auth_codes`");
			DBA::e("OPTIMIZE TABLE `cache`");
			DBA::e("OPTIMIZE TABLE `challenge`");
			DBA::e("OPTIMIZE TABLE `locks`");
			DBA::e("OPTIMIZE TABLE `oembed`");
			DBA::e("OPTIMIZE TABLE `parsed_url`");
			DBA::e("OPTIMIZE TABLE `profile_check`");
			DBA::e("OPTIMIZE TABLE `session`");
			DBA::e("OPTIMIZE TABLE `tokens`");
			DBA::e("OPTIMIZE TABLE `process`");
			Logger::info('Optimize finished');
		}

		DI::config()->set('system', 'cache_last_cleared', time());
	}
}
