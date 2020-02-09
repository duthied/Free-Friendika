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

namespace Friendica\Content;

use Friendica\Model\FileTag;

/**
 * A content helper class for displaying items
 */
class Item
{
	/**
	 * Return array with details for categories and folders for an item
	 *
	 * @param array $item
	 * @return [array, array]
	 *
	 * [
	 *      [ // categories array
	 *          {
	 *               'name': 'category name',
	 *               'removeurl': 'url to remove this category',
	 *               'first': 'is the first in this array? true/false',
	 *               'last': 'is the last in this array? true/false',
	 *           } ,
	 *           ....
	 *       ],
	 *       [ //folders array
	 *			{
	 *               'name': 'folder name',
	 *               'removeurl': 'url to remove this folder',
	 *               'first': 'is the first in this array? true/false',
	 *               'last': 'is the last in this array? true/false',
	 *           } ,
	 *           ....
	 *       ]
	 *  ]
	 */
	public function determineCategoriesTerms(array $item)
	{
		$categories = [];
		$folders = [];
		$first = true;

		foreach (FileTag::fileToArray($item['file'] ?? '', 'category') as $savedFolderName) {
			if (!empty($item['author-link'])) {
				$url = $item['author-link'] . "?category=" . rawurlencode($savedFolderName);
			} else {
				$url = '#';
			}
			$categories[] = [
				'name' => $savedFolderName,
				'url' => $url,
				'removeurl' => ((local_user() == $item['uid']) ? 'filerm/' . $item['id'] . '?cat=' . rawurlencode($savedFolderName) : ""),
				'first' => $first,
				'last' => false
			];
			$first = false;
		}

		if (count($categories)) {
			$categories[count($categories) - 1]['last'] = true;
		}

		if (local_user() == $item['uid']) {
			foreach (FileTag::fileToArray($item['file'] ?? '') as $savedFolderName) {
				$folders[] = [
					'name' => $savedFolderName,
					'url' => "#",
					'removeurl' => ((local_user() == $item['uid']) ? 'filerm/' . $item['id'] . '?term=' . rawurlencode($savedFolderName) : ""),
					'first' => $first,
					'last' => false
				];
				$first = false;
			}
		}

		if (count($folders)) {
			$folders[count($folders) - 1]['last'] = true;
		}

		return [$categories, $folders];
	}
}
