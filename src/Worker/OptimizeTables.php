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
use Friendica\DI;

/**
 * Optimize tables that are known to grow and shrink all the time
 */
class OptimizeTables
{
	public static function execute()
	{

		if (!DI::lock()->acquire('optimize_tables', 0)) {
			Logger::warning('Lock could not be acquired');
			return;
		}

		Logger::info('Optimize start');

		DBA::optimizeTable('cache');
		DBA::optimizeTable('locks');
		DBA::optimizeTable('oembed');
		DBA::optimizeTable('parsed_url');
		DBA::optimizeTable('session');
		DBA::optimizeTable('post-engagement');
		DBA::optimizeTable('check-full-text-search');

		if (DI::config()->get('system', 'optimize_all_tables')) {
			DBA::optimizeTable('apcontact');
			DBA::optimizeTable('contact');
			DBA::optimizeTable('contact-relation');
			DBA::optimizeTable('conversation');
			DBA::optimizeTable('diaspora-contact');
			DBA::optimizeTable('diaspora-interaction');
			DBA::optimizeTable('fcontact');
			DBA::optimizeTable('gserver');
			DBA::optimizeTable('gserver-tag');
			DBA::optimizeTable('inbox-status');
			DBA::optimizeTable('item-uri');
			DBA::optimizeTable('notification');
			DBA::optimizeTable('notify');
			DBA::optimizeTable('photo');
			DBA::optimizeTable('post');
			DBA::optimizeTable('post-content');
			DBA::optimizeTable('post-delivery-data');
			DBA::optimizeTable('post-link');
			DBA::optimizeTable('post-thread');
			DBA::optimizeTable('post-thread-user');
			DBA::optimizeTable('post-user');
			DBA::optimizeTable('storage');
			DBA::optimizeTable('tag');
		}

		Logger::info('Optimize end');

		DI::lock()->release('optimize_tables');
	}
}
