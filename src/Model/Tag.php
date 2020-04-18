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

namespace Friendica\Model;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\Util\Strings;

/**
 * Class Tag
 *
 * This Model class handles tag table interactions.
 * This tables stores relevant tags related to posts, like hashtags and mentions.
 */
class Tag
{
	const UNKNOWN  = 0;
	const HASHTAG  = 1;
	const MENTION  = 2;
	const CATEGORY = 3;
	const FILE     = 5;
	/**
	 * An implicit mention is a mention in a comment body that is redundant with the threading information.
	 */
	const IMPLICIT_MENTION  = 8;
	/**
	 * An exclusive mention transfers the ownership of the post to the target account, usually a forum.
	 */
	const EXCLUSIVE_MENTION = 9;

	const TAG_CHARACTER = [
		self::HASHTAG           => '#',
		self::MENTION           => '@',
		self::IMPLICIT_MENTION  => '%',
		self::EXCLUSIVE_MENTION => '!',
	];

	/**
	 * Store tag/mention elements
	 *
	 * @param integer $uriid
	 * @param integer $type
	 * @param string $name
	 * @param string $url
	 */
	public static function store(int $uriid, int $type, string $name, string $url = '')
	{
		$name = trim($name, "\x00..\x20\xFF#!@");
		if (empty($name)) {
			return;
		}

		$cid = 0;
		$tagid = 0;

		if (in_array($type, [Tag::MENTION, Tag::EXCLUSIVE_MENTION, Tag::IMPLICIT_MENTION])) {
			if (empty($url)) {
				// No mention without a contact url
				return;
			}

			Logger::info('Get ID for contact', ['url' => $url]);

			$condition = ['nurl' => Strings::normaliseLink($url), 'uid' => 0, 'deleted' => false];
			$contact = DBA::selectFirst('contact', ['id'], $condition, ['order' => ['id']]);
			if (DBA::isResult($contact)) {
				$cid = $contact['id'];
			} else {
				// The contact wasn't found in the system (most likely some dead account)
				// We ensure that we only store a single entry by overwriting the previous name
				DBA::update('tag', ['name' => substr($name, 0, 96)], ['url' => $url]);
			}
		}

		if (empty($cid)) {
			$fields = ['name' => substr($name, 0, 96), 'url' => ''];

			if (!empty($url) && ($url != $name)) {
				$fields['url'] = strtolower($url);
			}
	
			$tag = DBA::selectFirst('tag', ['id'], $fields);
			if (!DBA::isResult($tag)) {
				DBA::insert('tag', $fields, true);
				$tagid = DBA::lastInsertId();
			} else {
				$tagid = $tag['id'];
			}
	
			if (empty($tagid)) {
				Logger::error('No tag id created', $fields);
				return;
			}
		}

		DBA::insert('post-tag', ['uri-id' => $uriid, 'type' => $type, 'tid' => $tagid, 'cid' => $cid], true);

		Logger::info('Stored tag/mention', ['uri-id' => $uriid, 'tag-id' => $tagid, 'contact-id' => $cid]);
	}

	/**
	 * Store tag/mention elements
	 *
	 * @param integer $uriid
	 * @param string $hash
	 * @param string $name
	 * @param string $url
	 */
	public static function storeByHash(int $uriid, string $hash, string $name, string $url = '')
	{
		if ($hash == self::TAG_CHARACTER[self::MENTION]) {
			$type = self::MENTION;
		} elseif ($hash == self::TAG_CHARACTER[self::EXCLUSIVE_MENTION]) {
			$type = self::EXCLUSIVE_MENTION;
		} elseif ($hash == self::TAG_CHARACTER[self::IMPLICIT_MENTION]) {
			$type = self::IMPLICIT_MENTION;
		} elseif ($hash == self::TAG_CHARACTER[self::HASHTAG]) {
			$type = self::HASHTAG;
		} else {
			return;
		}

		self::store($uriid, $type, $name, $url);
	}

	/**
	 * Store tags and mentions from the body
	 * 
	 * @param integer $uriid URI-Id
	 * @param string  $body   Body of the post
	 * @param string  $tags   Accepted tags
	 */
	public static function storeFromBody(int $uriid, string $body, string $tags = null)
	{
		if (is_null($tags)) {
			$tags =  self::TAG_CHARACTER[self::HASHTAG] . self::TAG_CHARACTER[self::MENTION] . self::TAG_CHARACTER[self::EXCLUSIVE_MENTION];
		}

		if (!preg_match_all("/([" . $tags . "])\[url\=([^\[\]]*)\]([^\[\]]*)\[\/url\]/ism", $body, $result, PREG_SET_ORDER)) {
			return;
		}

		foreach ($result as $tag) {
			self::storeByHash($uriid, $tag[1], $tag[3], $tag[2]);
		}
	}

	/**
	 * Remove tag/mention
	 *
	 * @param integer $uriid
	 * @param integer $type
	 * @param string $name
	 * @param string $url
	 */
	public static function remove(int $uriid, int $type, string $name, string $url = '')
	{
		$tag = DBA::fetchFirst("SELECT `id` FROM `tag` INNER JOIN `post-tag` ON `post-tag`.`tid` = `tag`.`id`
			WHERE `uri-id` = ? AND `type` = ? AND `name` = ? AND `url` = ?", $uriid, $type, $name, $url);
		if (!DBA::isResult($tag)) {
			return;
		}
		Logger::info('Removing tag/mention', ['uri-id' => $uriid, 'tid' => $tag['id'], 'name' => $name, 'url' => $url]);
		DBA::delete('post-tag', ['uri-id' => $uriid, 'tid' => $tag['id']]);
	}

	/**
	 * Remove tag/mention
	 *
	 * @param integer $uriid
	 * @param string $hash
	 * @param string $name
	 * @param string $url
	 */
	public static function removeByHash(int $uriid, string $hash, string $name, string $url = '')
	{
		if ($hash == self::TAG_CHARACTER[self::MENTION]) {
			$type = self::MENTION;
		} elseif ($hash == self::TAG_CHARACTER[self::EXCLUSIVE_MENTION]) {
			$type = self::EXCLUSIVE_MENTION;
		} elseif ($hash == self::TAG_CHARACTER[self::IMPLICIT_MENTION]) {
			$type = self::IMPLICIT_MENTION;
		} elseif ($hash == self::TAG_CHARACTER[self::HASHTAG]) {
			$type = self::HASHTAG;
		} else {
			return;
		}

		self::remove($uriid, $type, $name, $url);
	}
}
