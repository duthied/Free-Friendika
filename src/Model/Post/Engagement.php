<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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
use Friendica\Protocol\ActivityPub\Receiver;
use Friendica\Protocol\Relay;
use Friendica\Util\DateTimeFormat;

class Engagement
{
	const KEYWORDS = ['source', 'server', 'from', 'to', 'group', 'tag', 'network', 'platform', 'visibility'];

	/**
	 * Store engagement data from an item array
	 *
	 * @param array $item
	 * @return int uri-id of the engagement post if newly inserted, 0 on update
	 */
	public static function storeFromItem(array $item): int
	{
		if (in_array($item['verb'], [Activity::FOLLOW, Activity::VIEW, Activity::READ])) {
			Logger::debug('Technical activities are not stored', ['uri-id' => $item['uri-id'], 'parent-uri-id' => $item['parent-uri-id'], 'verb' => $item['verb']]);
			return 0;
		}

		$parent = Post::selectFirst(['uri-id', 'created', 'author-id', 'owner-id', 'uid', 'private', 'contact-contact-type', 'language', 'network',
			'title', 'content-warning', 'body', 'author-contact-type', 'author-nick', 'author-addr', 'author-gsid', 'owner-contact-type', 'owner-nick', 'owner-addr', 'owner-gsid'],
			['uri-id' => $item['parent-uri-id']]);

		if ($parent['created'] < self::getCreationDateLimit(false)) {
			Logger::debug('Post is too old', ['uri-id' => $item['uri-id'], 'parent-uri-id' => $item['parent-uri-id'], 'created' => $parent['created']]);
			return 0;
		}

		$store = ($item['gravity'] != Item::GRAVITY_PARENT);

		if (!$store) {
			$store = Contact::hasFollowers($parent['owner-id']);
		}

		if (!$store && ($parent['owner-id'] != $parent['author-id'])) {
			$store = Contact::hasFollowers($parent['author-id']);
		}

		if (!$store) {
			$tagList = Relay::getSubscribedTags();
			foreach (array_column(Tag::getByURIId($item['parent-uri-id'], [Tag::HASHTAG]), 'name') as $tag) {
				if (in_array(mb_strtolower($tag), $tagList)) {
					$store = true;
					break;
				}
			}
		}

		$mediatype = self::getMediaType($item['parent-uri-id']);

		if (!$store) {
			$store = !empty($mediatype);
		}

		$searchtext = self::getSearchTextForItem($parent);
		if (!$store) {
			$language = !empty($parent['language']) ? (array_key_first(json_decode($parent['language'], true)) ?? '') : '';
			$store    = DI::userDefinedChannel()->match($searchtext, $language);
		}

		$engagement = [
			'uri-id'       => $item['parent-uri-id'],
			'owner-id'     => $parent['owner-id'],
			'contact-type' => $parent['contact-contact-type'],
			'media-type'   => $mediatype,
			'language'     => $parent['language'],
			'searchtext'   => $searchtext,
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
			return 0;
		}
		$exists = DBA::exists('post-engagement', ['uri-id' => $engagement['uri-id']]);
		if ($exists) {
			$ret = DBA::update('post-engagement', $engagement, ['uri-id' => $engagement['uri-id']]);
			Logger::debug('Engagement updated', ['uri-id' => $engagement['uri-id'], 'ret' => $ret]);
		} else {
			$ret = DBA::insert('post-engagement', $engagement);
			Logger::debug('Engagement inserted', ['uri-id' => $engagement['uri-id'], 'ret' => $ret]);
		}
		return ($ret && !$exists) ? $engagement['uri-id'] : 0;
	}

	public static function getSearchTextForActivity(string $content, int $author_id, array $tags, array $receivers): string
	{
		$author = Contact::getById($author_id);

		$item = [
			'uri-id'              => 0,
			'network'             => Protocol::ACTIVITYPUB,
			'title'               => '',
			'content-warning'     => '',
			'body'                => $content,
			'private'             => Item::PRIVATE,
			'author-id'           => $author_id,
			'author-contact-type' => $author['contact-type'],
			'author-nick'         => $author['nick'],
			'author-addr'         => $author['addr'],
			'author-gsid'         => $author['gsid'],
			'owner-id'            => $author_id,
			'owner-contact-type'  => $author['contact-type'],
			'owner-nick'          => $author['nick'],
			'owner-addr'          => $author['addr'],
			'owner-gsid'          => $author['gsid'],
		];

		foreach ($receivers as $receiver) {
			if ($receiver == Receiver::PUBLIC_COLLECTION) {
				$item['private'] = Item::PUBLIC;
			}
		}

		return self::getSearchText($item, $receivers, $tags);
	}

	public static function getSearchTextForUriId(int $uri_id, bool $refresh = false): string
	{
		if (!$refresh) {
			$engagement = DBA::selectFirst('post-engagement', ['searchtext'], ['uri-id' => $uri_id]);
			if (!empty($engagement['searchtext'])) {
				return $engagement['searchtext'];
			}
		}

		$post = Post::selectFirstPost(['uri-id', 'network', 'title', 'content-warning', 'body', 'private',
			'author-id', 'author-contact-type', 'author-nick', 'author-addr', 'author-gsid',
			'owner-id', 'owner-contact-type', 'owner-nick', 'owner-addr', 'owner-gsid'], ['uri-id' => $uri_id]);
		return self::getSearchTextForItem($post);
	}

	private static function getSearchTextForItem(array $item): string
	{
		$receivers = array_column(Tag::getByURIId($item['uri-id'], [Tag::MENTION, Tag::IMPLICIT_MENTION, Tag::EXCLUSIVE_MENTION, Tag::AUDIENCE]), 'url');
		$tags      = array_column(Tag::getByURIId($item['uri-id'], [Tag::HASHTAG]), 'name');
		return self::getSearchText($item, $receivers, $tags);
	}

	private static function getSearchText(array $item, array $receivers, array $tags): string
	{
		$body = '[nosmile]network_' . $item['network'];

		if (!empty($item['author-gsid'])) {
			$gserver = DBA::selectFirst('gserver', ['platform', 'nurl'], ['id' => $item['author-gsid']]);
			$platform = preg_replace( '/[\W]/', '', $gserver['platform'] ?? '');
			if (!empty($platform)) {
				$body .= ' platform_' . $platform;
			}
			$body .= ' server_' . parse_url($gserver['nurl'], PHP_URL_HOST);
		}

		if (($item['owner-contact-type'] == Contact::TYPE_COMMUNITY) && !empty($item['owner-gsid']) && ($item['owner-gsid'] != ($item['author-gsid'] ?? 0))) {
			$gserver = DBA::selectFirst('gserver', ['platform', 'nurl'], ['id' => $item['owner-gsid']]);
			$platform = preg_replace( '/[\W]/', '', $gserver['platform'] ?? '');
			if (!empty($platform) && !strpos($body, 'platform_' . $platform)) {
				$body .= ' platform_' . $platform;
			}
			$body .= ' server_' . parse_url($gserver['nurl'], PHP_URL_HOST);
		}

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

		if (in_array(Contact::TYPE_COMMUNITY, [$item['author-contact-type'], $item['owner-contact-type']])) {
			$body .= ' source:group';
		} elseif ($item['author-contact-type'] == Contact::TYPE_PERSON) {
			$body .= ' source:person';
		} elseif ($item['author-contact-type'] == Contact::TYPE_NEWS) {
			$body .= ' source:service';
		} elseif ($item['author-contact-type'] == Contact::TYPE_ORGANISATION) {
			$body .= ' source:organization';
		} elseif ($item['author-contact-type'] == Contact::TYPE_RELAY) {
			$body .= ' source:application';
		}

		if ($item['author-contact-type'] == Contact::TYPE_COMMUNITY) {
			$body .= ' group_' . $item['author-nick'] . ' group_' . $item['author-addr'];
		} elseif (in_array($item['author-contact-type'], [Contact::TYPE_PERSON, Contact::TYPE_NEWS, Contact::TYPE_ORGANISATION])) {
			$body .= ' from_' . $item['author-nick'] . ' from_' . $item['author-addr'];
		}

		if ($item['author-id'] !=  $item['owner-id']) {
			if ($item['owner-contact-type'] == Contact::TYPE_COMMUNITY) {
				$body .= ' group_' . $item['owner-nick'] . ' group_' . $item['owner-addr'];
			} elseif (in_array($item['owner-contact-type'], [Contact::TYPE_PERSON, Contact::TYPE_NEWS, Contact::TYPE_ORGANISATION])) {
				$body .= ' from_' . $item['owner-nick'] . ' from_' . $item['owner-addr'];
			}
		}

		foreach ($receivers as $receiver) {
			$contact = Contact::getByURL($receiver, false, ['nick', 'addr', 'contact-type']);
			if (empty($contact)) {
				continue;
			}

			if (($contact['contact-type'] == Contact::TYPE_COMMUNITY) && !strpos($body, 'group_' . $contact['addr'])) {
				$body .= ' group_' . $contact['nick'] . ' group_' . $contact['addr'];
			} elseif (in_array($contact['contact-type'], [Contact::TYPE_PERSON, Contact::TYPE_NEWS, Contact::TYPE_ORGANISATION])) {
				$body .= ' to_' . $contact['nick'] . ' to_' . $contact['addr'];
			}
		}

		foreach ($tags as $tag) {
			$body .= ' tag_' . $tag;
		}

		$body .= ' ' . $item['title'] . ' ' . $item['content-warning'] . ' ' . $item['body'];

		return BBCode::toSearchText($body, $item['uri-id']);
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
		$limit = self::getCreationDateLimit(true);
		if (empty($limit)) {
			Logger::notice('Expiration limit not reached');
			return;
		}
		DBA::delete('post-engagement', ["`created` < ?", $limit]);
		Logger::notice('Cleared expired engagements', ['limit' => $limit, 'rows' => DBA::affectedRows()]);
	}

	private static function getCreationDateLimit(bool $forDeletion): string
	{
		$posts = DI::config()->get('channel', 'engagement_post_limit');
		if (!empty($posts)) {
			$limit = DBA::selectToArray('post-engagement', ['created'], [], ['limit' => [$posts, 1], 'order' => ['created' => true]]);
			if (!empty($limit)) {
				return $limit[0]['created'];
			} elseif ($forDeletion) {
				return '';
			}
		}

		return DateTimeFormat::utc('now - ' . DI::config()->get('channel', 'engagement_hours') . ' hour');
	}

	public static function escapeKeywords(string $fullTextSearch): string
	{
		foreach (Engagement::KEYWORDS as $keyword) {
			$fullTextSearch = preg_replace('~(' . $keyword . '):(.[\w\*@\.-]+)~', '$1_$2', $fullTextSearch);
		}
		return $fullTextSearch;
	}
}
