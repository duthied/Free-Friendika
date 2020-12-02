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

namespace Friendica\Model\Post;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Model\Item;
use Friendica\Model\Tag;

class Delayed
{
	/**
	 * Insert a new delayed post
	 *
	 * @param string $delayed
	 * @param array $item
	 * @param integer $notify
	 * @param array $taglist
	 * @param array $attachments
	 * @return bool insert success
	 */ 
	public static function add(string $delayed, array $item, int $notify = 0, array $taglist = [], array $attachments = [])
	{
		if (empty($item['uri']) || empty($item['uid']) || self::exists($item['uri'])) {
			return false;
		}

		Logger::notice('Adding post for delayed publishing', ['uid' => $item['uid'], 'delayed' => $delayed, 'uri' => $item['uri']]);

		Worker::add(['priority' => PRIORITY_HIGH, 'delayed' => $delayed], 'DelayedPublish', $item, $notify, $taglist, $attachments);
		return DBA::insert('delayed-post', ['uri' => $item['uri'], 'uid' => $item['uid'], 'delayed' => $delayed], Database::INSERT_IGNORE);
	}

	/**
	 * Delete a delayed post
	 *
	 * @param string $uri
	 *
	 * @return bool delete success
	 */
	private static function delete(string $uri)
	{
		return DBA::delete('delayed-post', ['uri' => $uri]);
	}

	/**
	 * Check if an entry exists
	 *
	 * @param string $uri
	 *
	 * @return bool "true" if an entry with that URI exists
	 */
	public static function exists(string $uri)
	{
		return DBA::exists('delayed-post', ['uri' => $uri]);
	}

	/**
	 * Publish a delayed post
	 *
	 * @param array $item
	 * @param integer $notify
	 * @param array $taglist
	 * @param array $attachments
	 * @return bool
	 */
	public static function publish(array $item, int $notify = 0, array $taglist = [], array $attachments = [])
	{
		$id = Item::insert($item, $notify);

		Logger::notice('Post stored', ['id' => $id, 'uid' => $item['uid'], 'cid' => $item['contact-id']]);

		// It should always contain an URI since this is needed to create a delayed post entry
		if (!empty($item['uri'])) {
			$result = self::delete($item['uri']);
			Logger::notice('Delayed post entry deleted', ['result' => $result, 'uri' => $item['uri']]);
		}

		if (!empty($id) && (!empty($taglist) || !empty($attachments))) {
			$feeditem = Item::selectFirst(['uri-id'], ['id' => $id]);

			foreach ($taglist as $tag) {
				Tag::store($feeditem['uri-id'], Tag::HASHTAG, $tag);
			}

			foreach ($attachments as $attachment) {
				$attachment['uri-id'] = $feeditem['uri-id'];
				Media::insert($attachment);
			}
		}

		return $id;
	}
}
