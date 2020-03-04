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
use Friendica\DI;

/**
 * The script is called from time to time to clean the database entries and remove orphaned data.
 */
class DBClean {
	public static function execute($stage = 0) {

		if (!DI::config()->get('system', 'dbclean', false)) {
			return;
		}

		if ($stage == 0) {
			self::forkCleanProcess();
		} else {
			self::removeOrphans($stage);
		}
	}

	/**
	 * Fork the different DBClean processes
	 */
	private static function forkCleanProcess() {
		// Get the expire days for step 8 and 9
		$days = DI::config()->get('system', 'dbclean-expire-days', 0);

		for ($i = 1; $i <= 10; $i++) {
			// Execute the background script for a step when it isn't finished.
			// Execute step 8 and 9 only when $days is defined.
			if (!DI::config()->get('system', 'finished-dbclean-'.$i, false) && (($i < 8) || ($i > 9) || ($days > 0))) {
				Worker::add(PRIORITY_LOW, 'DBClean', $i);
			}
		}
	}

	/**
	 * Remove orphaned database entries
	 *
	 * @param integer $stage What should be deleted?
	 *
	 * Values for $stage:
	 * ------------------
	 *  1:    Old global item entries from item table without user copy.
	 *  2:    Items without parents.
	 *  3:    Orphaned data from thread table.
	 *  4:    Orphaned data from notify table.
	 *  5:    Orphaned data from notify-threads table.
	 *  6:    Orphaned data from sign table.
	 *  7:    Orphaned data from term table.
	 *  8:    Expired threads.
	 *  9:    Old global item entries from expired threads.
	 * 10:    Old conversations.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function removeOrphans($stage) {
		// We split the deletion in many small tasks
		$limit = DI::config()->get('system', 'dbclean-expire-limit', 1000);

		// Get the expire days for step 8 and 9
		$days = DI::config()->get('system', 'dbclean-expire-days', 0);
		$days_unclaimed = DI::config()->get('system', 'dbclean-expire-unclaimed', 90);

		if ($days_unclaimed == 0) {
			$days_unclaimed = $days;
		}

		if ($stage == 1) {
			if ($days_unclaimed <= 0) {
				return;
			}

			$last_id = DI::config()->get('system', 'dbclean-last-id-1', 0);

			Logger::log("Deleting old global item entries from item table without user copy. Last ID: ".$last_id);
			$r = DBA::p("SELECT `id`, `guid` FROM `item` WHERE `uid` = 0 AND
						NOT EXISTS (SELECT `guid` FROM `item` AS `i` WHERE `item`.`guid` = `i`.`guid` AND `i`.`uid` != 0) AND
						`received` < UTC_TIMESTAMP() - INTERVAL ? DAY AND `id` >= ?
					ORDER BY `id` LIMIT ?", $days_unclaimed, $last_id, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				Logger::log("found global item orphans: ".$count);
				while ($orphan = DBA::fetch($r)) {
					$last_id = $orphan["id"];
					Logger::info('Delete global orphan item', ['id' => $orphan['id'], 'guid' => $orphan['guid']]);
					DBA::delete('item', ['id' => $orphan["id"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 1, $last_id);
			} else {
				Logger::log("No global item orphans found");
			}
			DBA::close($r);
			Logger::log("Done deleting ".$count." old global item entries from item table without user copy. Last ID: ".$last_id);

			DI::config()->set('system', 'dbclean-last-id-1', $last_id);
		} elseif ($stage == 2) {
			$last_id = DI::config()->get('system', 'dbclean-last-id-2', 0);

			Logger::log("Deleting items without parents. Last ID: ".$last_id);
			$r = DBA::p("SELECT `id`, `guid` FROM `item`
					WHERE NOT EXISTS (SELECT `id` FROM `item` AS `i` WHERE `item`.`parent` = `i`.`id`)
					AND `id` >= ? ORDER BY `id` LIMIT ?", $last_id, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				Logger::log("found item orphans without parents: ".$count);
				while ($orphan = DBA::fetch($r)) {
					$last_id = $orphan["id"];
					Logger::info('Delete orphan item', ['id' => $orphan['id'], 'guid' => $orphan['guid']]);
					DBA::delete('item', ['id' => $orphan["id"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 2, $last_id);
			} else {
				Logger::log("No item orphans without parents found");
			}
			DBA::close($r);
			Logger::log("Done deleting ".$count." items without parents. Last ID: ".$last_id);

			DI::config()->set('system', 'dbclean-last-id-2', $last_id);

			if ($count < $limit) {
				DI::config()->set('system', 'finished-dbclean-2', true);
			}
		} elseif ($stage == 3) {
			$last_id = DI::config()->get('system', 'dbclean-last-id-3', 0);

			Logger::log("Deleting orphaned data from thread table. Last ID: ".$last_id);
			$r = DBA::p("SELECT `iid` FROM `thread`
					WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`parent` = `thread`.`iid`) AND `iid` >= ?
					ORDER BY `iid` LIMIT ?", $last_id, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				Logger::log("found thread orphans: ".$count);
				while ($orphan = DBA::fetch($r)) {
					$last_id = $orphan["iid"];
					DBA::delete('thread', ['iid' => $orphan["iid"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 3, $last_id);
			} else {
				Logger::log("No thread orphans found");
			}
			DBA::close($r);
			Logger::log("Done deleting ".$count." orphaned data from thread table. Last ID: ".$last_id);

			DI::config()->set('system', 'dbclean-last-id-3', $last_id);

			if ($count < $limit) {
				DI::config()->set('system', 'finished-dbclean-3', true);
			}
		} elseif ($stage == 4) {
			$last_id = DI::config()->get('system', 'dbclean-last-id-4', 0);

			Logger::log("Deleting orphaned data from notify table. Last ID: ".$last_id);
			$r = DBA::p("SELECT `iid`, `id` FROM `notify`
					WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`id` = `notify`.`iid`) AND `id` >= ?
					ORDER BY `id` LIMIT ?", $last_id, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				Logger::log("found notify orphans: ".$count);
				while ($orphan = DBA::fetch($r)) {
					$last_id = $orphan["id"];
					DBA::delete('notify', ['iid' => $orphan["iid"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 4, $last_id);
			} else {
				Logger::log("No notify orphans found");
			}
			DBA::close($r);
			Logger::log("Done deleting ".$count." orphaned data from notify table. Last ID: ".$last_id);

			DI::config()->set('system', 'dbclean-last-id-4', $last_id);

			if ($count < $limit) {
				DI::config()->set('system', 'finished-dbclean-4', true);
			}
		} elseif ($stage == 5) {
			$last_id = DI::config()->get('system', 'dbclean-last-id-5', 0);

			Logger::log("Deleting orphaned data from notify-threads table. Last ID: ".$last_id);
			$r = DBA::p("SELECT `id` FROM `notify-threads`
					WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`parent` = `notify-threads`.`master-parent-item`) AND `id` >= ?
					ORDER BY `id` LIMIT ?", $last_id, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				Logger::log("found notify-threads orphans: ".$count);
				while ($orphan = DBA::fetch($r)) {
					$last_id = $orphan["id"];
					DBA::delete('notify-threads', ['id' => $orphan["id"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 5, $last_id);
			} else {
				Logger::log("No notify-threads orphans found");
			}
			DBA::close($r);
			Logger::log("Done deleting ".$count." orphaned data from notify-threads table. Last ID: ".$last_id);

			DI::config()->set('system', 'dbclean-last-id-5', $last_id);

			if ($count < $limit) {
				DI::config()->set('system', 'finished-dbclean-5', true);
			}
		} elseif ($stage == 6) {
			$last_id = DI::config()->get('system', 'dbclean-last-id-6', 0);

			Logger::log("Deleting orphaned data from sign table. Last ID: ".$last_id);
			$r = DBA::p("SELECT `iid`, `id` FROM `sign`
					WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`id` = `sign`.`iid`) AND `id` >= ?
					ORDER BY `id` LIMIT ?", $last_id, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				Logger::log("found sign orphans: ".$count);
				while ($orphan = DBA::fetch($r)) {
					$last_id = $orphan["id"];
					DBA::delete('sign', ['iid' => $orphan["iid"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 6, $last_id);
			} else {
				Logger::log("No sign orphans found");
			}
			DBA::close($r);
			Logger::log("Done deleting ".$count." orphaned data from sign table. Last ID: ".$last_id);

			DI::config()->set('system', 'dbclean-last-id-6', $last_id);

			if ($count < $limit) {
				DI::config()->set('system', 'finished-dbclean-6', true);
			}
		} elseif ($stage == 7) {
			$last_id = DI::config()->get('system', 'dbclean-last-id-7', 0);

			Logger::log("Deleting orphaned data from term table. Last ID: ".$last_id);
			$r = DBA::p("SELECT `oid`, `tid` FROM `term`
					WHERE NOT EXISTS (SELECT `id` FROM `item` WHERE `item`.`id` = `term`.`oid`) AND `tid` >= ?
					ORDER BY `tid` LIMIT ?", $last_id, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				Logger::log("found term orphans: ".$count);
				while ($orphan = DBA::fetch($r)) {
					$last_id = $orphan["tid"];
					DBA::delete('term', ['oid' => $orphan["oid"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 7, $last_id);
			} else {
				Logger::log("No term orphans found");
			}
			DBA::close($r);
			Logger::log("Done deleting ".$count." orphaned data from term table. Last ID: ".$last_id);

			DI::config()->set('system', 'dbclean-last-id-7', $last_id);

			if ($count < $limit) {
				DI::config()->set('system', 'finished-dbclean-7', true);
			}
		} elseif ($stage == 8) {
			if ($days <= 0) {
				return;
			}

			$last_id = DI::config()->get('system', 'dbclean-last-id-8', 0);

			Logger::log("Deleting expired threads. Last ID: ".$last_id);
			$r = DBA::p("SELECT `thread`.`iid` FROM `thread`
	                                INNER JOIN `contact` ON `thread`.`contact-id` = `contact`.`id` AND NOT `notify_new_posts`
	                                WHERE `thread`.`received` < UTC_TIMESTAMP() - INTERVAL ? DAY
	                                        AND NOT `thread`.`mention` AND NOT `thread`.`starred`
	                                        AND NOT `thread`.`wall` AND NOT `thread`.`origin`
	                                        AND `thread`.`uid` != 0 AND `thread`.`iid` >= ?
	                                        AND NOT `thread`.`iid` IN (SELECT `parent` FROM `item`
	                                                        WHERE (`item`.`starred` OR (`item`.`resource-id` != '')
	                                                                OR (`item`.`file` != '') OR (`item`.`event-id` != '')
	                                                                OR (`item`.`attach` != '') OR `item`.`wall` OR `item`.`origin`)
	                                                                AND `item`.`parent` = `thread`.`iid`)
	                                ORDER BY `thread`.`iid` LIMIT ?", $days, $last_id, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				Logger::log("found expired threads: ".$count);
				while ($thread = DBA::fetch($r)) {
					$last_id = $thread["iid"];
					DBA::delete('thread', ['iid' => $thread["iid"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 8, $last_id);
			} else {
				Logger::log("No expired threads found");
			}
			DBA::close($r);
			Logger::log("Done deleting ".$count." expired threads. Last ID: ".$last_id);

			DI::config()->set('system', 'dbclean-last-id-8', $last_id);
		} elseif ($stage == 9) {
			if ($days <= 0) {
				return;
			}

			$last_id = DI::config()->get('system', 'dbclean-last-id-9', 0);
			$till_id = DI::config()->get('system', 'dbclean-last-id-8', 0);

			Logger::log("Deleting old global item entries from expired threads from ID ".$last_id." to ID ".$till_id);
			$r = DBA::p("SELECT `id`, `guid` FROM `item` WHERE `uid` = 0 AND
						NOT EXISTS (SELECT `guid` FROM `item` AS `i` WHERE `item`.`guid` = `i`.`guid` AND `i`.`uid` != 0) AND
						`received` < UTC_TIMESTAMP() - INTERVAL 90 DAY AND `id` >= ? AND `id` <= ?
					ORDER BY `id` LIMIT ?", $last_id, $till_id, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				Logger::log("found global item entries from expired threads: ".$count);
				while ($orphan = DBA::fetch($r)) {
					$last_id = $orphan["id"];
					Logger::info('Delete expired thread item', ['id' => $orphan['id'], 'guid' => $orphan['guid']]);
					DBA::delete('item', ['id' => $orphan["id"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 9, $last_id);
			} else {
				Logger::log("No global item entries from expired threads");
			}
			DBA::close($r);
			Logger::log("Done deleting ".$count." old global item entries from expired threads. Last ID: ".$last_id);

			DI::config()->set('system', 'dbclean-last-id-9', $last_id);
		} elseif ($stage == 10) {
			$last_id = DI::config()->get('system', 'dbclean-last-id-10', 0);
			$days = intval(DI::config()->get('system', 'dbclean_expire_conversation', 90));

			Logger::log("Deleting old conversations. Last created: ".$last_id);
			$r = DBA::p("SELECT `received`, `item-uri` FROM `conversation`
					WHERE `received` < UTC_TIMESTAMP() - INTERVAL ? DAY
					ORDER BY `received` LIMIT ?", $days, $limit);
			$count = DBA::numRows($r);
			if ($count > 0) {
				Logger::log("found old conversations: ".$count);
				while ($orphan = DBA::fetch($r)) {
					$last_id = $orphan["received"];
					DBA::delete('conversation', ['item-uri' => $orphan["item-uri"]]);
				}
				Worker::add(PRIORITY_MEDIUM, 'DBClean', 10, $last_id);
			} else {
				Logger::log("No old conversations found");
			}
			DBA::close($r);
			Logger::log("Done deleting ".$count." conversations. Last created: ".$last_id);

			DI::config()->set('system', 'dbclean-last-id-10', $last_id);
		}
	}
}
