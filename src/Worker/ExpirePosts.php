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
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;

class ExpirePosts
{
	/**
	 * Expire posts and remove unused item-uri entries
	 *
	 * @return void
	 */
	public static function execute()
	{
		$a = DI::app();

		self::deleteExpiredOriginPosts();

		self::deleteUnusedItemUri();

		self::deleteExpiredExternalPosts();

		// update nodeinfo data after everything is cleaned up
		Worker::add(['priority' => $a->queue['priority'], 'created' => $a->queue['created'], 'dont_fork' => true], 'NodeInfo');
	}

	/**
	 * Delete expired origin posts and orphaned post related table entries
	 *
	 * @return void
	 */
	private static function deleteExpiredOriginPosts()
	{
		Logger::info('Delete expired posts');
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

		Logger::info('Delete expired posts - done');
	}

	/**
	 * Delete unused item-uri entries
	 */
	private static function deleteUnusedItemUri()
	{
		$a = DI::app();

		// We have to avoid deleting newly created "item-uri" entries.
		// So we fetch a post that had been stored yesterday and only delete older ones.
		$item = Post::selectFirst(['uri-id'], ["`uid` = ? AND `received` < UTC_TIMESTAMP() - INTERVAL ? DAY", 0, 1],
			['order' => ['received' => true]]);
		if (empty($item['uri-id'])) {
			Logger::warning('No item with uri-id found - we better quit here');
			return;
		}
		Logger::notice('Start deleting orphaned URI-ID', ['last-id' => $item['uri-id']]);
		$uris = DBA::select('item-uri', ['id'], ["`id` < ?
			AND NOT EXISTS(SELECT `uri-id` FROM `post` WHERE `uri-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `parent-uri-id` FROM `post` WHERE `parent-uri-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `thr-parent-id` FROM `post` WHERE `thr-parent-id` = `item-uri`.`id`)
			AND NOT EXISTS(SELECT `external-id` FROM `post` WHERE `external-id` = `item-uri`.`id`)", $item['uri-id']]);

		$affected_count = 0;
		while ($rows = DBA::toArray($uris, false, 100)) {
			$ids = array_column($rows, 'id');
			DBA::delete('item-uri', ['id' => $ids]);
			$affected_count += DBA::affectedRows();
			Logger::info('Deleted', ['rows' => $affected_count]);
		}
		DBA::close($uris);
		Logger::notice('Orphaned URI-ID entries removed', ['rows' => $affected_count]);
	}

	/**
	 * Delete old external post entries
	 */
	private static function deleteExpiredExternalPosts()
	{
		$expire_days = DI::config()->get('system', 'dbclean-expire-days');
		$expire_days_unclaimed = DI::config()->get('system', 'dbclean-expire-unclaimed');
		if (empty($expire_days_unclaimed)) {
			$expire_days_unclaimed = $expire_days;
		}

		$limit = DI::config()->get('system', 'dbclean-expire-limit');
		if (empty($limit)) {
			return;
		}

		if (!empty($expire_days)) {
			Logger::notice('Start collecting expired threads', ['expiry_days' => $expire_days]);
			$uris = DBA::select('item-uri', ['id'], ["`id` IN
				(SELECT `uri-id` FROM `post-thread` WHERE `received` < UTC_TIMESTAMP() - INTERVAL ? DAY
					AND NOT `uri-id` IN (SELECT `uri-id` FROM `post-thread-user`
						WHERE (`mention` OR `starred` OR `wall` OR `pinned`) AND `uri-id` = `post-thread`.`uri-id`)
					AND NOT `uri-id` IN (SELECT `uri-id` FROM `post-category`
						WHERE `uri-id` = `post-thread`.`uri-id`)
					AND NOT `uri-id` IN (SELECT `uri-id` FROM `post-media`
						WHERE `uri-id` = `post-thread`.`uri-id`)
					AND NOT `uri-id` IN (SELECT `parent-uri-id` FROM `post-user` INNER JOIN `contact` ON `contact`.`id` = `contact-id` AND `notify_new_posts`
						WHERE `parent-uri-id` = `post-thread`.`uri-id`)
					AND NOT `uri-id` IN (SELECT `parent-uri-id` FROM `post-user`
						WHERE (`origin` OR `event-id` != 0 OR `post-type` = ?) AND `parent-uri-id` = `post-thread`.`uri-id`)
					AND NOT `uri-id` IN (SELECT `uri-id` FROM `post-content`
						WHERE `resource-id` != 0 AND `uri-id` = `post-thread`.`uri-id`))",
				$expire_days, Item::PT_PERSONAL_NOTE]);

			Logger::notice('Start deleting expired threads');
			$affected_count = 0;
			while ($rows = DBA::toArray($uris, false, 100)) {
				$ids = array_column($rows, 'id');
				DBA::delete('item-uri', ['id' => $ids]);
				$affected_count += DBA::affectedRows();
			}
			DBA::close($uris);

			Logger::notice('Deleted expired threads', ['rows' => $affected_count]);
		}

		if (!empty($expire_days_unclaimed)) {
			Logger::notice('Start collecting unclaimed public items', ['expiry_days' => $expire_days_unclaimed]);
			$uris = DBA::select('item-uri', ['id'], ["`id` IN
				(SELECT `uri-id` FROM `post-user` WHERE `gravity` = ? AND `uid` = ? AND `received` < UTC_TIMESTAMP() - INTERVAL ? DAY
					AND NOT `uri-id` IN (SELECT `parent-uri-id` FROM `post-user` AS `i` WHERE `i`.`uid` != ?
						AND `i`.`parent-uri-id` = `post-user`.`uri-id`)
					AND NOT `uri-id` IN (SELECT `parent-uri-id` FROM `post-user` AS `i` WHERE `i`.`uid` = ?
						AND `i`.`parent-uri-id` = `post-user`.`uri-id` AND `i`.`received` > UTC_TIMESTAMP() - INTERVAL ? DAY))",
				GRAVITY_PARENT, 0, $expire_days_unclaimed, 0, 0, $expire_days_unclaimed]);

			Logger::notice('Start deleting unclaimed public items');
			$affected_count = 0;
			while ($rows = DBA::toArray($uris, false, 100)) {
				$ids = array_column($rows, 'id');
				DBA::delete('item-uri', ['id' => $ids]);
				$affected_count += DBA::affectedRows();
			}
			DBA::close($uris);
			Logger::notice('Deleted unclaimed public items', ['rows' => $affected_count]);
		}
	}
}
