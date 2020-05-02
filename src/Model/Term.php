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

use Friendica\Database\DBA;

/**
 * Class Term
 *
 * This Model class handles term table interactions.
 * This tables stores relevant terms related to posts, photos and searches, like hashtags, mentions and
 * user-applied categories.
 */
class Term
{
    const UNKNOWN           = 0;
    const CATEGORY          = 3;
    const FILE              = 5;

    const OBJECT_TYPE_POST  = 1;

	/**
	 * Generates the legacy item.file field string from an item ID.
	 * Includes only file and category terms.
	 *
	 * @param int $item_id
	 * @return string
	 * @throws \Exception
	 */
	public static function fileTextFromItemId($item_id)
	{
		$file_text = '';

		$condition = ['otype' => self::OBJECT_TYPE_POST, 'oid' => $item_id, 'type' => [self::FILE, self::CATEGORY]];
		$tags = DBA::selectToArray('term', ['type', 'term', 'url'], $condition);
		foreach ($tags as $tag) {
			if ($tag['type'] == self::CATEGORY) {
				$file_text .= '<' . $tag['term'] . '>';
			} else {
				$file_text .= '[' . $tag['term'] . ']';
			}
		}

		return $file_text;
	}

	/**
	 * Inserts new terms for the provided item ID based on the legacy item.file field BBCode content.
	 * Deletes all previous file terms for the same item ID.
	 *
	 * @param integer $item_id item id
	 * @param         $files
	 * @return void
	 * @throws \Exception
	 */
	public static function insertFromFileFieldByItemId($item_id, $files)
	{
		$message = Item::selectFirst(['uid', 'deleted'], ['id' => $item_id]);
		if (!DBA::isResult($message)) {
			return;
		}

		// Clean up all tags
		DBA::delete('term', ['otype' => self::OBJECT_TYPE_POST, 'oid' => $item_id, 'type' => [self::FILE, self::CATEGORY]]);

		if ($message["deleted"]) {
			return;
		}

		$message['file'] = $files;

		if (preg_match_all("/\[(.*?)\]/ism", $message["file"], $files)) {
			foreach ($files[1] as $file) {
				DBA::insert('term', [
					'uid' => $message["uid"],
					'oid' => $item_id,
					'otype' => self::OBJECT_TYPE_POST,
					'type' => self::FILE,
					'term' => $file
				]);
			}
		}

		if (preg_match_all("/\<(.*?)\>/ism", $message["file"], $files)) {
			foreach ($files[1] as $file) {
				DBA::insert('term', [
					'uid' => $message["uid"],
					'oid' => $item_id,
					'otype' => self::OBJECT_TYPE_POST,
					'type' => self::CATEGORY,
					'term' => $file
				]);
			}
		}
	}
}
