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

namespace Friendica\Core\Worker;

use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\DateTimeFormat;

/**
 * Contains the class for jobs that are executed in an interval
 */
class Cron
{
	/**
	 * Runs the cron processes
	 *
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function run()
	{
		Logger::info('Add cron entries');

		// Check for spooled items
		Worker::add(['priority' => PRIORITY_HIGH, 'force_priority' => true], 'SpoolPost');

		// Run the cron job that calls all other jobs
		Worker::add(['priority' => PRIORITY_MEDIUM, 'force_priority' => true], 'Cron');

		// Cleaning dead processes
		self::killStaleWorkers();

		// Remove old entries from the workerqueue
		self::cleanWorkerQueue();

		// Directly deliver or requeue posts
		self::deliverPosts();
	}

	/**
	 * fix the queue entry if the worker process died
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function killStaleWorkers()
	{
		$entries = DBA::select(
			'workerqueue',
			['id', 'pid', 'executed', 'priority', 'command', 'parameter'],
			['NOT `done` AND `pid` != 0'],
			['order' => ['priority', 'retrial', 'created']]
		);

		while ($entry = DBA::fetch($entries)) {
			if (!posix_kill($entry["pid"], 0)) {
				DBA::update('workerqueue', ['executed' => DBA::NULL_DATETIME, 'pid' => 0], ['id' => $entry["id"]]);
			} else {
				// Kill long running processes

				// Define the maximum durations
				$max_duration_defaults = [PRIORITY_CRITICAL => 720, PRIORITY_HIGH => 10, PRIORITY_MEDIUM => 60, PRIORITY_LOW => 180, PRIORITY_NEGLIGIBLE => 720];
				$max_duration          = $max_duration_defaults[$entry['priority']];

				$argv = json_decode($entry['parameter'], true);
				if (!empty($entry['command'])) {
					$command = $entry['command'];
				} elseif (!empty($argv)) {
					$command = array_shift($argv);
				} else {
					return;
				}

				$command = basename($command);

				// How long is the process already running?
				$duration = (time() - strtotime($entry["executed"])) / 60;
				if ($duration > $max_duration) {
					Logger::notice('Worker process took too much time - killed', ['duration' => number_format($duration, 3), 'max' => $max_duration, 'id' => $entry["id"], 'pid' => $entry["pid"], 'command' => $command]);
					posix_kill($entry["pid"], SIGTERM);

					// We killed the stale process.
					// To avoid a blocking situation we reschedule the process at the beginning of the queue.
					// Additionally we are lowering the priority. (But not PRIORITY_CRITICAL)
					$new_priority = $entry['priority'];
					if ($entry['priority'] == PRIORITY_HIGH) {
						$new_priority = PRIORITY_MEDIUM;
					} elseif ($entry['priority'] == PRIORITY_MEDIUM) {
						$new_priority = PRIORITY_LOW;
					} elseif ($entry['priority'] != PRIORITY_CRITICAL) {
						$new_priority = PRIORITY_NEGLIGIBLE;
					}
					DBA::update('workerqueue', ['executed' => DBA::NULL_DATETIME, 'created' => DateTimeFormat::utcNow(), 'priority' => $new_priority, 'pid' => 0], ['id' => $entry["id"]]
					);
				} else {
					Logger::info('Process runtime is okay', ['duration' => number_format($duration, 3), 'max' => $max_duration, 'id' => $entry["id"], 'pid' => $entry["pid"], 'command' => $command]);
				}
			}
		}
		DBA::close($entries);
	}

	/**
	 * Remove old entries from the workerqueue
	 *
	 * @return void
	 */
	private static function cleanWorkerQueue()
	{
		DBA::delete('workerqueue', ["`done` AND `executed` < ?", DateTimeFormat::utc('now - 1 hour')]);

		// Optimizing this table only last seconds
		if (DI::config()->get('system', 'optimize_tables')) {
			// We are acquiring the two locks from the worker to avoid locking problems
			if (DI::lock()->acquire(Worker::LOCK_PROCESS, 10)) {
				if (DI::lock()->acquire(Worker::LOCK_WORKER, 10)) {
					DBA::e("OPTIMIZE TABLE `workerqueue`");
					DBA::e("OPTIMIZE TABLE `process`");
					DI::lock()->release(Worker::LOCK_WORKER);
				}
				DI::lock()->release(Worker::LOCK_PROCESS);
			}
		}
	}

	/**
	 * Directly deliver AP messages or requeue them.
	 *
	 * This function is placed here as a safeguard. Even when the worker queue is completely blocked, messages will be delivered.
	 */
	private static function deliverPosts()
	{
		$deliveries = DBA::p("SELECT `item-uri`.`uri` AS `inbox`, MAX(`failed`) AS `failed` FROM `post-delivery` INNER JOIN `item-uri` ON `item-uri`.`id` = `post-delivery`.`inbox-id` GROUP BY `inbox`");
		while ($delivery = DBA::fetch($deliveries)) {
			if ($delivery['failed'] == 0) {
				$result = ActivityPub\Delivery::deliver($delivery['inbox']);
				Logger::info('Drectly deliver inbox', ['inbox' => $delivery['inbox'], 'result' => $result['success']]);
				continue;
			} elseif ($delivery['failed'] < 3) {
				$priority = PRIORITY_HIGH;
			} elseif ($delivery['failed'] < 6) {
				$priority = PRIORITY_MEDIUM;
			} elseif ($delivery['failed'] < 8) {
				$priority = PRIORITY_LOW;
			} else {
				$priority = PRIORITY_NEGLIGIBLE;
			}

			if ($delivery['failed'] >= DI::config()->get('system', 'worker_defer_limit')) {
				Logger::info('Removing failed deliveries', ['inbox' => $delivery['inbox'], 'failed' => $delivery['failed']]);
				Post\Delivery::removeFailed($delivery['inbox']);
			}

			if (Worker::add($priority, 'APDelivery', '', 0, $delivery['inbox'], 0)) {
				Logger::info('Missing APDelivery worker added for inbox', ['inbox' => $delivery['inbox'], 'failed' => $delivery['failed'], 'priority' => $priority]);
			}
		}
	}
}
