<?php
/**
 * @file include/text.php
 */

use Friendica\Model\FileTag;

/**
 * return array with details for categories and folders for an item
 *
 * @param array $item
 * @return array
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
function get_cats_and_terms($item)
{
	$categories = [];
	$folders = [];
	$first = true;

	foreach (FileTag::fileToArray($item['file'] ?? '', 'category') as $savedFolderName) {
		$categories[] = [
			'name' => $savedFolderName,
			'url' => "#",
			'removeurl' => ((local_user() == $item['uid']) ? 'filerm/' . $item['id'] . '?f=&cat=' . rawurlencode($savedFolderName) : ""),
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
				'removeurl' => ((local_user() == $item['uid']) ? 'filerm/' . $item['id'] . '?f=&term=' . rawurlencode($savedFolderName) : ""),
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
