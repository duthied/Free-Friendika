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

class TagUpdate
{
	public static function execute()
	{
		$messages = DBA::p("SELECT `oid`,`item`.`guid`, `item`.`created`, `item`.`received` FROM `term` INNER JOIN `item` ON `item`.`id`=`term`.`oid` WHERE `term`.`otype` = 1 AND `term`.`guid` = ''");

		Logger::log('fetched messages: ' . DBA::numRows($messages));
		while ($message = DBA::fetch($messages)) {
			if ($message['uid'] == 0) {
				$global = true;

				DBA::update('term', ['global' => true], ['otype' => TERM_OBJ_POST, 'guid' => $message['guid']]);
			} else {
				$global = (DBA::count('term', ['uid' => 0, 'otype' => TERM_OBJ_POST, 'guid' => $message['guid']]) > 0);
			}

			$fields = ['guid' => $message['guid'], 'created' => $message['created'],
				'received' => $message['received'], 'global' => $global];
			DBA::update('term', $fields, ['otype' => TERM_OBJ_POST, 'oid' => $message['oid']]);
		}

		DBA::close($messages);

		$messages = DBA::select('item', ['guid'], ['uid' => 0]);

		Logger::log('fetched messages: ' . DBA::numRows($messages));
		while ($message = DBA::fetch($messages)) {
			DBA::update('item', ['global' => true], ['guid' => $message['guid']]);
		}

		DBA::close($messages);
	}
}
