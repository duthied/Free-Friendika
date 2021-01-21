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
 * This class handles FileTag related functions
 *
 * post categories and "save to file" use the same item.file table for storage.
 * We will differentiate the different uses by wrapping categories in angle brackets
 * and save to file categories in square brackets.
 * To do this we need to escape these characters if they appear in our tag.
 */
class FileTag
{
	/**
	 * URL encode <, >, left and right brackets
	 *
	 * @param string $s String to be URL encoded.
	 *
	 * @return string   The URL encoded string.
	 */
	private static function encode($s)
	{
		return str_replace(['<', '>', '[', ']'], ['%3c', '%3e', '%5b', '%5d'], $s);
	}

	/**
	 * URL decode <, >, left and right brackets
	 *
	 * @param string $s The URL encoded string to be decoded
	 *
	 * @return string   The decoded string.
	 */
	private static function decode($s)
	{
		return str_replace(['%3c', '%3e', '%5b', '%5d'], ['<', '>', '[', ']'], $s);
	}

	/**
	 * Get file tags from array
	 *
	 * ex. given [music,video] return <music><video> or [music][video]
	 *
	 * @param array  $array A list of tags.
	 * @param string $type  Optional file type.
	 *
	 * @return string       A list of file tags.
	 */
	public static function arrayToFile(array $array, string $type = 'file')
	{
		$tag_list = '';
		if ($type == 'file') {
			$lbracket = '[';
			$rbracket = ']';
		} else {
			$lbracket = '<';
			$rbracket = '>';
		}

		foreach ($array as $item) {
			if (strlen($item)) {
				$tag_list .= $lbracket . self::encode(trim($item)) . $rbracket;
			}
		}

		return $tag_list;
	}

	/**
	 * Get tag list from file tags
	 *
	 * ex. given <music><video>[friends], return [music,video] or [friends]
	 *
	 * @param string $file File tags
	 * @param string $type Optional file type.
	 *
	 * @return array        List of tag names.
	 */
	public static function fileToArray(string $file, string $type = 'file')
	{
		$matches = [];
		$return = [];

		if ($type == 'file') {
			$cnt = preg_match_all('/\[(.*?)\]/', $file, $matches, PREG_SET_ORDER);
		} else {
			$cnt = preg_match_all('/<(.*?)>/', $file, $matches, PREG_SET_ORDER);
		}

		if ($cnt) {
			foreach ($matches as $match) {
				$return[] = self::decode($match[1]);
			}
		}

		return $return;
	}

	/**
	 * Get file tags from list
	 *
	 * ex. given music,video return <music><video> or [music][video]
	 * @param string $list A comma delimited list of tags.
	 * @param string $type Optional file type.
	 *
	 * @return string       A list of file tags.
	 * @deprecated since 2019.06 use arrayToFile() instead
	 */
	public static function listToFile(string $list, string $type = 'file')
	{
		$list_array = explode(',', $list);

		return self::arrayToFile($list_array, $type);
	}

	/**
	 * Add tag to file
	 *
	 * @param int    $uid     Unique identity.
	 * @param int    $item_id Item identity.
	 * @param string $file    File tag.
	 *
	 * @return boolean      A value indicating success or failure.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function saveFile($uid, $item_id, $file)
	{
		if (!intval($uid)) {
			return false;
		}

		$item = Post::selectFirst(['uri-id'], ['id' => $item_id, 'uid' => $uid]);
		if (DBA::isResult($item)) {
			$stored_file = Post\Category::getTextByURIId($item['uri-id'], $uid);

			if (!stristr($stored_file, '[' . self::encode($file) . ']')) {
				Post\Category::storeTextByURIId($item['uri-id'], $uid, $stored_file . '[' . self::encode($file) . ']');
			}
		}

		return true;
	}

	/**
	 * Remove tag from file
	 *
	 * @param int     $uid     Unique identity.
	 * @param int     $item_id Item identity.
	 * @param string  $file    File tag.
	 * @param boolean $cat     Optional value indicating the term type (i.e. Category or File)
	 *
	 * @return boolean      A value indicating success or failure.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function unsaveFile($uid, $item_id, $file, $cat = false)
	{
		if (!intval($uid)) {
			return false;
		}

		if ($cat == true) {
			$pattern = '<' . self::encode($file) . '>';
		} else {
			$pattern = '[' . self::encode($file) . ']';
		}

		$item = Post::selectFirst(['uri-id'], ['id' => $item_id, 'uid' => $uid]);
		if (!DBA::isResult($item)) {
			return false;
		}

		$file = Post\Category::getTextByURIId($item['uri-id'], $uid);

		Post\Category::storeTextByURIId($item['uri-id'], $uid, str_replace($pattern, '', $file));

		return true;
	}
}
