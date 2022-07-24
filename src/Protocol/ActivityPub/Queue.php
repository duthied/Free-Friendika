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
 * This class handles the processing of incoming posts
 */
class Queue
{
	/**
	 * Add activity to the queue
	 *
	 * @param array $activity
	 * @param string $type
	 * @param integer $uid
	 * @param string $http_signer
	 * @param boolean $push
	 * @return array
	 */
	public static function add(array $activity, string $type, int $uid, string $http_signer, bool $push): array
	{
		$fields = [
			'activity-id' => $activity['id'],
			'object-id'   => $activity['object_id'],
			'type'        => $type,
			'object-type' => $activity['object_type'],
			'activity'    => json_encode($activity, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
			'received'    => DateTimeFormat::utcNow(),
			'push'        => $push,
		];

		if (!empty($activity['reply-to-id'])) {
			$fields['in-reply-to-id'] = $activity['reply-to-id'];
		}

		if (!empty($activity['context'])) {
			$fields['conversation'] = $activity['context'];
		} elseif (!empty($activity['conversation'])) {
			$fields['conversation'] = $activity['conversation'];
		}

		if (!empty($activity['object_object_type'])) {
			$fields['object-object-type'] = $activity['object_object_type'];
		}

		if (!empty($http_signer)) {
			$fields['signer'] = $http_signer;
		}

		DBA::insert('inbox-entry', $fields, Database::INSERT_IGNORE);

		$queue = DBA::selectFirst('inbox-entry', ['id'], ['activity-id' => $activity['id']]);
		if (!empty($queue['id'])) {
			$activity['entry-id'] = $queue['id'];
			DBA::insert('inbox-entry-receiver', ['queue-id' => $queue['id'], 'uid' => $uid], Database::INSERT_IGNORE);
		}
		return $activity;
	}

	/**
	 * Remove activity from the queue
	 *
	 * @param array $activity
	 * @return void
	 */
	public static function remove(array $activity = [])
	{
		if (empty($activity['entry-id'])) {
			return;
		}
		DBA::delete('inbox-entry', ['id' => $activity['entry-id']]);
	}

	/**
	 * Delete all entries that depend on the given worker id
	 *
	 * @param integer $wid
	 * @return void
	 */
	public static function deleteByWorkerId(int $wid)
	{
		$entries = DBA::select('inbox-entry', ['id'], ['wid' => $wid]);
		while ($entry = DBA::fetch($entries)) {
			self::deleteById($entry['id']);
		}
		DBA::close($entries);
	}

	/**
	 * Delete recursively an entry and all their children
	 *
	 * @param integer $id
	 * @return void
	 */
	public static function deleteById(int $id)
	{
		$entry = DBA::selectFirst('inbox-entry', ['id', 'object-id'], ['id' => $id]);
		if (empty($entry)) {
			return;
		}

		$children = DBA::select('inbox-entry', ['id'], ['in-reply-to-id' => $entry['object-id']]);
		while ($child = DBA::fetch($children)) {
			self::deleteById($child['id']);
		}
		DBA::close($children);
		DBA::delete('inbox-entry', ['id' => $entry['id']]);
	}

	/**
	 * Set the worker id for the queue entry
	 *
	 * @param array $activity
	 * @param int   $wid
	 * @return void
	 */
	public static function setWorkerId(array $activity, int $wid)
	{
		if (empty($activity['entry-id']) || empty($wid)) {
			return;
		}
		DBA::update('inbox-entry', ['wid' => $wid], ['id' => $activity['entry-id']]);
	}

	/**
	 * Check if there is an assigned worker task
	 *
	 * @param array $activity
	 * @return bool
	 */
	public static function hasWorker(array $activity = []): bool
	{
		if (empty($activity['worker-id'])) {
			return false;
		}
		return DBA::exists('workerqueue', ['id' => $activity['worker-id'], 'done' => false]);
	}

	/**
	 * Process the activity with the given id
	 *
	 * @param integer $id
	 * @return void
	 */
	public static function process(int $id)
	{
		$entry = DBA::selectFirst('inbox-entry', [], ['id' => $id]);
		if (empty($entry)) {
			return;
		}

		Logger::debug('Processing queue entry', ['id' => $entry['id'], 'type' => $entry['type'], 'object-type' => $entry['object-type'], 'uri' => $entry['object-id'], 'in-reply-to' => $entry['in-reply-to-id']]);

		$activity = json_decode($entry['activity'], true);
		$type     = $entry['type'];
		$push     = $entry['push'];

		$activity['entry-id']        = $entry['id'];
		$activity['worker-id']       = $entry['wid'];
		$activity['recursion-depth'] = 0;

		$receivers = DBA::select('inbox-entry-receiver', ['uid'], ['queue-id' => $entry['id']]);
		while ($receiver = DBA::fetch($receivers)) {
			if (!in_array($receiver['uid'], $activity['receiver'])) {
				$activity['receiver'][] = $receiver['uid'];
			}
		}
		DBA::close($receivers);

		if (!Receiver::routeActivities($activity, $type, $push)) {
			self::remove($activity);
		}
	}

	/**
	 * Process all activities
	 *
	 * @return void
	 */
	public static function processAll()
	{
		$entries = DBA::select('inbox-entry', ['id', 'type', 'object-type', 'object-id', 'in-reply-to-id'], ["`wid` IS NULL"], ['order' => ['id' => true]]);
		while ($entry = DBA::fetch($entries)) {
			// We don't need to process entries that depend on already existing entries.
			if (!empty($entry['in-reply-to-id']) && DBA::exists('inbox-entry', ["`id` != ? AND `object-id` = ?", $entry['id'], $entry['in-reply-to-id']])) {
				continue;
			}
			Logger::debug('Process leftover entry', $entry);
			self::process($entry['id']);
		}
	}

	/**
	 * Clear old activities
	 *
	 * @return void
	 */
	public static function clear()
	{
		// We delete all entries that aren't associated with a worker entry after seven days.
		// The other entries are deleted when the worker deferred for too long.
		DBA::delete('inbox-entry', ["`wid` IS NULL AND `received` < ?", DateTimeFormat::utc('now - 7 days')]);

		// Optimizing this table only last seconds
		if (DI::config()->get('system', 'optimize_tables')) {
			Logger::info('Optimize start');
			DBA::e("OPTIMIZE TABLE `inbox-entry`");
			Logger::info('Optimize end');
		}
	}

	/**
	 * Process all activities that are children of a given post url
	 *
	 * @param string $uri
	 * @return void
	 */
	public static function processReplyByUri(string $uri)
	{
		$entries = DBA::select('inbox-entry', ['id'], ["`in-reply-to-id` = ? AND `object-id` != ?", $uri, $uri]);
		while ($entry = DBA::fetch($entries)) {
			self::process($entry['id']);
		}
	}
}
