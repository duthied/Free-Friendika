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

use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\FileTag;
use Friendica\Model\Tag;

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

	/**
	 * This function removes the tag $tag from the text $body and replaces it with
	 * the appropriate link.
	 *
	 * @param string  $body        the text to replace the tag in
	 * @param string  $inform      a comma-seperated string containing everybody to inform
	 * @param integer $profile_uid the user id to replace the tag for (0 = anyone)
	 * @param string  $tag         the tag to replace
	 * @param string  $network     The network of the post
	 *
	 * @return array|bool ['replaced' => $replaced, 'contact' => $contact];
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function replaceTag(&$body, &$inform, $profile_uid, $tag, $network = '')
	{
		$replaced = false;

		//is it a person tag?
		if (Tag::isType($tag, Tag::MENTION, Tag::IMPLICIT_MENTION, Tag::EXCLUSIVE_MENTION)) {
			$tag_type = substr($tag, 0, 1);
			//is it already replaced?
			if (strpos($tag, '[url=')) {
				// Checking for the alias that is used for OStatus
				$pattern = '/[@!]\[url\=(.*?)\](.*?)\[\/url\]/ism';
				if (preg_match($pattern, $tag, $matches)) {
					$data = Contact::getByURL($matches[1], 0, ['alias', 'nick'], false);

					if ($data['alias'] != '') {
						$newtag = '@[url=' . $data['alias'] . ']' . $data['nick'] . '[/url]';
					}
				}

				return $replaced;
			}

			//get the person's name
			$name = substr($tag, 1);

			// Sometimes the tag detection doesn't seem to work right
			// This is some workaround
			$nameparts = explode(' ', $name);
			$name = $nameparts[0];

			// Try to detect the contact in various ways
			if (strpos($name, 'http://') || strpos($name, '@')) {
				$contact = Contact::getByURLForUser($name, $profile_uid, []);
			} else {
				$contact = false;
				$fields = ['id', 'url', 'nick', 'name', 'alias', 'network', 'forum', 'prv'];

				if (strrpos($name, '+')) {
					// Is it in format @nick+number?
					$tagcid = intval(substr($name, strrpos($name, '+') + 1));
					$contact = DBA::selectFirst('contact', $fields, ['id' => $tagcid, 'uid' => $profile_uid]);
				}

				// select someone by nick in the current network
				if (!DBA::isResult($contact) && ($network != '')) {
					$condition = ["`nick` = ? AND `network` = ? AND `uid` = ?",
						$name, $network, $profile_uid];
					$contact = DBA::selectFirst('contact', $fields, $condition);
				}

				// select someone by attag in the current network
				if (!DBA::isResult($contact) && ($network != '')) {
					$condition = ["`attag` = ? AND `network` = ? AND `uid` = ?",
						$name, $network, $profile_uid];
					$contact = DBA::selectFirst('contact', $fields, $condition);
				}

				//select someone by name in the current network
				if (!DBA::isResult($contact) && ($network != '')) {
					$condition = ['name' => $name, 'network' => $network, 'uid' => $profile_uid];
					$contact = DBA::selectFirst('contact', $fields, $condition);
				}

				// select someone by nick in any network
				if (!DBA::isResult($contact)) {
					$condition = ["`nick` = ? AND `uid` = ?", $name, $profile_uid];
					$contact = DBA::selectFirst('contact', $fields, $condition);
				}

				// select someone by attag in any network
				if (!DBA::isResult($contact)) {
					$condition = ["`attag` = ? AND `uid` = ?", $name, $profile_uid];
					$contact = DBA::selectFirst('contact', $fields, $condition);
				}

				// select someone by name in any network
				if (!DBA::isResult($contact)) {
					$condition = ['name' => $name, 'uid' => $profile_uid];
					$contact = DBA::selectFirst('contact', $fields, $condition);
				}
			}

			// Check if $contact has been successfully loaded
			if (DBA::isResult($contact)) {
				if (strlen($inform) && (isset($contact['notify']) || isset($contact['id']))) {
					$inform .= ',';
				}

				if (isset($contact['id'])) {
					$inform .= 'cid:' . $contact['id'];
				} elseif (isset($contact['notify'])) {
					$inform  .= $contact['notify'];
				}

				$profile = $contact['url'];
				$newname = ($contact['name'] ?? '') ?: $contact['nick'];
			}

			//if there is an url for this persons profile
			if (isset($profile) && ($newname != '')) {
				$replaced = true;
				// create profile link
				$profile = str_replace(',', '%2c', $profile);
				$newtag = $tag_type.'[url=' . $profile . ']' . $newname . '[/url]';
				$body = str_replace($tag_type . $name, $newtag, $body);
			}
		}

		return ['replaced' => $replaced, 'contact' => $contact];
	}
}
