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

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\Database\DBA;

/**
 * Requeue posts that are stuck in the post-delivery table without a matching delivery job.
 * This should not happen in regular situations, this is a precaution.
 */
class RequeuePosts
{
	public static function execute()
	{
		$deliveries = DBA::p("SELECT `item-uri`.`uri` AS `inbox` FROM `post-delivery` INNER JOIN `item-uri` ON `item-uri`.`id` = `post-delivery`.`inbox-id` GROUP BY `inbox`");
		while ($delivery = DBA::fetch($deliveries)) {
			if (Worker::add(PRIORITY_HIGH, 'APDelivery', '', 0, $delivery['inbox'], 0)) {
				Logger::info('Missing APDelivery worker added for inbox', ['inbox' => $delivery['inbox']]);
			}
		}
		DBA::close($deliveries);
	}
}
