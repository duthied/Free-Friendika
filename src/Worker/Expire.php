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

use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;

/**
 * Expires old item entries
 */
class Expire
{
	public static function execute($param = '', $hook_function = '')
	{
		$a = DI::app();

		Hook::loadHooks();

		if ($param == 'delete') {
			Logger::log('Delete expired items', Logger::DEBUG);
			// physically remove anything that has been deleted for more than two months
			$condition = ["`gravity` = ? AND `deleted` AND `changed` < UTC_TIMESTAMP() - INTERVAL 60 DAY", GRAVITY_PARENT];
			$rows = Post::select(['guid', 'uri-id', 'uid'],  $condition);
			while ($row = Post::fetch($rows)) {
				Logger::info('Delete expired item', ['uri-id' => $row['uri-id'], 'guid' => $row['guid']]);
				if (DBStructure::existsTable('item')) {
					DBA::delete('item', ['parent-uri-id' => $row['uri-id'], 'uid' => $row['uid']]);
				}
				Post\User::delete(['parent-uri-id' => $row['uri-id'], 'uid' => $row['uid']]);
			}
			DBA::close($rows);

			Logger::info('Deleting orphaned post entries- start');
			$condition = ["NOT EXISTS (SELECT `uri-id` FROM `post-user` WHERE `post-user`.`uri-id` = `post`.`uri-id`)"];
			DBA::delete('post', $condition);
			Logger::info('Orphaned post entries deleted', ['rows' => DBA::affectedRows()]);

			Logger::info('Deleting orphaned post-content entries - start');
			$condition = ["NOT EXISTS (SELECT `uri-id` FROM `post-user` WHERE `post-user`.`uri-id` = `post-content`.`uri-id`)"];
			DBA::delete('post-content', $condition);
			Logger::info('Orphaned post-content entries deleted', ['rows' => DBA::affectedRows()]);

			Logger::info('Deleting orphaned post-thread entries - start');
			$condition = ["NOT EXISTS (SELECT `uri-id` FROM `post-user` WHERE `post-user`.`uri-id` = `post-thread`.`uri-id`)"];
			DBA::delete('post-thread', $condition);
			Logger::info('Orphaned post-thread entries deleted', ['rows' => DBA::affectedRows()]);

			Logger::info('Deleting orphaned post-thread-user entries - start');
			$condition = ["NOT EXISTS (SELECT `uri-id` FROM `post-user` WHERE `post-user`.`uri-id` = `post-thread-user`.`uri-id`)"];
			DBA::delete('post-thread-user', $condition);
			Logger::info('Orphaned post-thread-user entries deleted', ['rows' => DBA::affectedRows()]);

			Logger::log('Delete expired items - done', Logger::DEBUG);
			return;
		} elseif (intval($param) > 0) {
			$user = DBA::selectFirst('user', ['uid', 'username', 'expire'], ['uid' => $param]);
			if (DBA::isResult($user)) {
				Logger::log('Expire items for user '.$user['uid'].' ('.$user['username'].') - interval: '.$user['expire'], Logger::DEBUG);
				Item::expire($user['uid'], $user['expire']);
				Logger::log('Expire items for user '.$user['uid'].' ('.$user['username'].') - done ', Logger::DEBUG);
			}
			return;
		} elseif ($param == 'hook' && !empty($hook_function)) {
			foreach (Hook::getByName('expire') as $hook) {
				if ($hook[1] == $hook_function) {
					Logger::log("Calling expire hook '" . $hook[1] . "'", Logger::DEBUG);
					Hook::callSingle($a, 'expire', $hook, $data);
				}
			}
			return;
		}

		Logger::log('expire: start');

		Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
				'Expire', 'delete');

		$r = DBA::p("SELECT `uid`, `username` FROM `user` WHERE `expire` != 0");
		while ($row = DBA::fetch($r)) {
			Logger::log('Calling expiry for user '.$row['uid'].' ('.$row['username'].')', Logger::DEBUG);
			Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
					'Expire', (int)$row['uid']);
		}
		DBA::close($r);

		Logger::log('expire: calling hooks');
		foreach (Hook::getByName('expire') as $hook) {
			Logger::log("Calling expire hook for '" . $hook[1] . "'", Logger::DEBUG);
			Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true],
					'Expire', 'hook', $hook[1]);
		}

		Logger::log('expire: end');

		return;
	}
}
