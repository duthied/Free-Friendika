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

namespace Friendica\Model\Post;

use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Verb;
use Friendica\Protocol\Activity;
use Friendica\Util\DateTimeFormat;

// Channel

class Engagement
{
	/**
	 * Store engagement data from an item array
	 *
	 * @param array $item
	 * @return void
	 */
	public static function storeFromItem(array $item)
	{
		if (!in_array($item['network'], Protocol::FEDERATED)) {
			Logger::debug('No federated network', ['uri-id' => $item['uri-id'], 'parent-uri-id' => $item['parent-uri-id'], 'network' => $item['network']]);
			return;
		}

		if ($item['gravity'] == Item::GRAVITY_PARENT) {
			Logger::debug('Parent posts are not stored', ['uri-id' => $item['uri-id'], 'parent-uri-id' => $item['parent-uri-id']]);
			return;
		}

		if (($item['uid'] != 0) && ($item['gravity'] == Item::GRAVITY_COMMENT)) {
			Logger::debug('Non public comments are not stored', ['uri-id' => $item['uri-id'], 'parent-uri-id' => $item['parent-uri-id'], 'uid' => $item['uid']]);
			return;
		}

		if (in_array($item['verb'], [Activity::FOLLOW, Activity::VIEW, Activity::READ])) {
			Logger::debug('Technical activities are not stored', ['uri-id' => $item['uri-id'], 'parent-uri-id' => $item['parent-uri-id'], 'verb' => $item['verb']]);
			return;
		}

		$parent = Post::selectFirst(['created', 'author-id', 'uid', 'private', 'contact-contact-type'], ['uri-id' => $item['parent-uri-id']]);
		if ($parent['private'] != Item::PUBLIC) {
			Logger::debug('Non public posts are not stored', ['uri-id' => $item['uri-id'], 'parent-uri-id' => $item['parent-uri-id'], 'uid' => $parent['uid'], 'private' => $parent['private']]);
			return;
		}

		if ($parent['created'] < DateTimeFormat::utc('now - ' . DI::config()->get('channel', 'engagement_hours') . ' hour')) {
			Logger::debug('Post is too old', ['uri-id' => $item['uri-id'], 'parent-uri-id' => $item['parent-uri-id'], 'created' => $parent['created']]);
			return;
		}

		$engagement = [
			'uri-id'       => $item['parent-uri-id'],
			'author-id'    => $parent['author-id'],
			'contact-type' => $parent['contact-contact-type'],
			'created'      => $parent['created'],
			'comments'     => DBA::count('post', ['parent-uri-id' => $item['parent-uri-id'], 'gravity' => Item::GRAVITY_COMMENT]),
			'activities'   => DBA::count('post', [
				"`parent-uri-id` = ? AND `gravity` = ? AND NOT `vid` IN (?, ?, ?)",
				$item['parent-uri-id'], Item::GRAVITY_ACTIVITY,
				Verb::getID(Activity::FOLLOW), Verb::getID(Activity::VIEW), Verb::getID(Activity::READ)
			])
		];
		if (($engagement['comments'] == 0) && ($engagement['activities'] == 0)) {
			Logger::debug('No comments nor activities. Engagement not stored', ['fields' => $engagement]);
			return;
		}
		$ret = DBA::insert('post-engagement', $engagement, Database::INSERT_UPDATE);
		Logger::debug('Engagement stored', ['fields' => $engagement, 'ret' => $ret]);
	}

	/**
	 * Expire old engagement data
	 *
	 * @return void
	 */
	public static function expire()
	{
		DBA::delete('post-engagement', ["`created` < ?", DateTimeFormat::utc('now - ' . DI::config()->get('channel', 'engagement_hours') . ' hour')]);
		Logger::notice('Cleared expired engagements', ['rows' => DBA::affectedRows()]);
	}
}
