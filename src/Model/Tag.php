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
use Friendica\Content\Text\BBCode;

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

	public static function store(int $uriid, int $type, string $name, string $url = '')
	{
		$name = trim($name, "\x00..\x20\xFF#!@");
		if (empty($name)) {
			return;
		}

		$fields = ['name' => substr($name, 0, 64), 'type' => $type];

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

		DBA::insert('post-tag', ['uri-id' => $uriid, 'tid' => $tagid], true);

		Logger::info('Stored tag/mention', ['uri-id' => $uriid, 'tag-id' => $tagid, 'tag' => $fields]);
	}

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
	 * Store tags from the body
	 *
	 * @param integer $uriid
	 * @param string $body
	 */
	public static function storeFromBody(int $uriid, string $body)
	{
		$tags = BBCode::getTags($body);
		if (empty($tags)) {
			return;
		}

		foreach ($tags as $tag) {
			if ((substr($tag, 0, 1) != self::TAG_CHARACTER[self::HASHTAG]) || (strlen($tag) <= 1)) {
				Logger::info('Skip tag', ['uriid' => $uriid, 'tag' => $tag]);
				continue;
			}

			self::storeByHash($uriid, '#', $tag);
		}
	}
}
