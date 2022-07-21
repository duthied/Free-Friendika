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

		$activity['entry-id'] = $entry['id'];

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
		$entries = DBA::select('inbox-entry', ['id', 'type', 'object-type'], [], ['order' => ['id' => true]]);
		while ($entry = DBA::fetch($entries)) {
			self::process($entry['id']);
		}

		DBA::delete('inbox-entry', ["`received` < ?", DateTimeFormat::utc('now - 1 days')]);
	}

	/**
	 * Process all activities that are children of a given post url
	 *
	 * @param string $uri
	 * @return void
	 */
	public static function processReplyByUri(string $uri)
	{
		$entries = DBA::select('inbox-entry', ['id'], ['in-reply-to-id' => $uri], ['order' => ['id' => true]]);
		while ($entry = DBA::fetch($entries)) {
			self::process($entry['id']);
		}
	}
}
