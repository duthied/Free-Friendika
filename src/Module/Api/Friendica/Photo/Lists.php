<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Module\Api\Friendica\Photo;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Model\Contact;
use Friendica\Model\Photo;

/**
 * Returns all lists the user subscribes to.
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-list
 */
class Lists extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
		$uid  = BaseApi::getCurrentUserID();
		$type = $this->parameters['extension'] ?? '';

		$photos = Photo::selectToArray(['resource-id'], ["`uid` = ? AND NOT `photo-type` IN (?, ?)", $uid, Photo::CONTACT_AVATAR, Photo::CONTACT_BANNER],
			['order' => ['id'], 'group_by' => ['resource-id']]);
	
		$data = ['photo' => []];
		if (DBA::isResult($photos)) {
			foreach ($photos as $photo) {
				$element = DI::friendicaPhoto()->createFromId($photo['resource-id'], null, $uid, 'json', false);
	
				$element['thumb'] = end($element['link']);
				unset($element['link']);
	
				if ($type == 'xml') {
					$thumb = $element['thumb'];
					unset($element['thumb']);
					$data['photo'][] = ['@attributes' => $element, '1' => $thumb];
				} else {
					$data['photo'][] = $element;
				}
			}
		}

		$this->response->exit('statuses', $data, $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
