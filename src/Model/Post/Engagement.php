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

use Friendica\Content\Text\BBCode;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Model\Verb;
use Friendica\Protocol\Activity;
use Friendica\Protocol\Relay;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

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
		if (in_array($item['verb'], [Activity::FOLLOW, Activity::VIEW, Activity::READ])) {
			Logger::debug('Technical activities are not stored', ['uri-id' => $item['uri-id'], 'parent-uri-id' => $item['parent-uri-id'], 'verb' => $item['verb']]);
			return;
		}

		$parent = Post::selectFirst(['uri-id', 'created', 'author-id', 'owner-id', 'uid', 'private', 'contact-contact-type', 'language', 'network',
			'title', 'content-warning', 'body', 'author-contact-type', 'author-nick', 'author-addr', 'owner-contact-type', 'owner-nick', 'owner-addr'],
			['uri-id' => $item['parent-uri-id']]);

		if ($parent['created'] < DateTimeFormat::utc('now - ' . DI::config()->get('channel', 'engagement_hours') . ' hour')) {
			Logger::debug('Post is too old', ['uri-id' => $item['uri-id'], 'parent-uri-id' => $item['parent-uri-id'], 'created' => $parent['created']]);
			return;
		}

		$store = ($item['gravity'] != Item::GRAVITY_PARENT);

		if (!$store) {
			$store = Contact::hasFollowers($parent['owner-id']);
		}

		if (!$store) {
			$tagList = Relay::getSubscribedTags();
			foreach (array_column(Tag::getByURIId($item['parent-uri-id'], [Tag::HASHTAG]), 'name') as $tag) {
				if (in_array($tag, $tagList)) {
					$store = true;
					break;
				}
			}
		}

		$mediatype = self::getMediaType($item['parent-uri-id']);

		if (!$store) {
			$mediatype = !empty($mediatype);
		}

		$engagement = [
			'uri-id'       => $item['parent-uri-id'],
			'owner-id'     => $parent['owner-id'],
			'contact-type' => $parent['contact-contact-type'],
			'media-type'   => $mediatype,
			'language'     => $parent['language'],
			'searchtext'   => self::getSearchText($parent),
			'created'      => $parent['created'],
			'restricted'   => !in_array($item['network'], Protocol::FEDERATED) || ($parent['private'] != Item::PUBLIC),
			'comments'     => DBA::count('post', ['parent-uri-id' => $item['parent-uri-id'], 'gravity' => Item::GRAVITY_COMMENT]),
			'activities'   => DBA::count('post', [
				"`parent-uri-id` = ? AND `gravity` = ? AND NOT `vid` IN (?, ?, ?)",
				$item['parent-uri-id'], Item::GRAVITY_ACTIVITY,
				Verb::getID(Activity::FOLLOW), Verb::getID(Activity::VIEW), Verb::getID(Activity::READ)
			])
		];
		if (!$store && ($engagement['comments'] == 0) && ($engagement['activities'] == 0)) {
			Logger::debug('No media, follower, subscribed tags, comments or activities. Engagement not stored', ['fields' => $engagement]);
			return;
		}
		$ret = DBA::insert('post-engagement', $engagement, Database::INSERT_UPDATE);
		Logger::debug('Engagement stored', ['fields' => $engagement, 'ret' => $ret]);
	}

	private static function getSearchText(array $item): string
	{
		$body = '[nosmile]network:' . $item['network'];

		switch ($item['private']) {
			case Item::PUBLIC:
				$body .= ' visibility:public';
				break;
			case Item::UNLISTED:
				$body .= ' visibility:unlisted';
				break;
			case Item::PRIVATE:
				$body .= ' visibility:private';
				break;
		}

		if ($item['author-contact-type'] == Contact::TYPE_COMMUNITY) {
			$body .= ' group:' . $item['author-nick'] . ' group:' . $item['author-addr'];
		} elseif (in_array($item['author-contact-type'], [Contact::TYPE_PERSON, Contact::TYPE_NEWS, Contact::TYPE_ORGANISATION])) {
			$body .= ' from:' . $item['author-nick'] . ' from:' . $item['author-addr'];
		}

		if ($item['author-id'] !=  $item['owner-id']) {
			if ($item['owner-contact-type'] == Contact::TYPE_COMMUNITY) {
				$body .= ' group:' . $item['owner-nick'] . ' group:' . $item['owner-addr'];
			} elseif (in_array($item['owner-contact-type'], [Contact::TYPE_PERSON, Contact::TYPE_NEWS, Contact::TYPE_ORGANISATION])) {
				$body .= ' from:' . $item['owner-nick'] . ' from:' . $item['owner-addr'];
			}
		}

		foreach (Tag::getByURIId($item['uri-id'], [Tag::MENTION, Tag::IMPLICIT_MENTION, Tag::EXCLUSIVE_MENTION, Tag::AUDIENCE]) as $tag) {
			$contact = Contact::getByURL($tag['name'], false, ['nick', 'addr', 'contact-type']);
			if (empty($contact)) {
				continue;
			}

			if (($contact['contact-type'] == Contact::TYPE_COMMUNITY) && !strpos($body, 'group:' . $contact['addr'])) {
				$body .= ' group:' . $contact['nick'] . ' group:' . $contact['addr'];
			} elseif (in_array($contact['contact-type'], [Contact::TYPE_PERSON, Contact::TYPE_NEWS, Contact::TYPE_ORGANISATION])) {
				$body .= ' to:' . $contact['nick'] . ' to:' . $contact['addr'];
			}
		}

		foreach (Tag::getByURIId($item['uri-id'], [Tag::HASHTAG]) as $tag) {
			$body .= ' tag:' . $tag['name'];
		}

		$body .= ' ' . $item['title'] . ' ' . $item['content-warning'] . ' ' . $item['body'];

		$body = preg_replace("~\[url\=.*\]https?:.*\[\/url\]~", '', $body);

		$body = Post\Media::addAttachmentsToBody($item['uri-id'], $body, [Post\Media::IMAGE]);
		$text = BBCode::toPlaintext($body, false);
		$text = preg_replace(Strings::autoLinkRegEx(), '', $text);

		do {
			$oldtext = $text;
			$text = str_replace(['  ', "\n", "\r"], ' ', $text);
		} while ($oldtext != $text);

		return $text;
	}

	private static function getMediaType(int $uri_id): int
	{
		$media = Post\Media::getByURIId($uri_id);
		$type  = 0;
		foreach ($media as $entry) {
			if ($entry['type'] == Post\Media::IMAGE) {
				$type = $type | 1;
			} elseif ($entry['type'] == Post\Media::VIDEO) {
				$type = $type | 2;
			} elseif ($entry['type'] == Post\Media::AUDIO) {
				$type = $type | 4;
			}
		}
		return $type;
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
