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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Network\HTTPException;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Network;

/**
 * ActivityPub Objects
 */
class Objects extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		if (empty($parameters['guid'])) {
			throw new HTTPException\BadRequestException();
		}

		if (!ActivityPub::isRequest()) {
			DI::baseUrl()->redirect(str_replace('objects/', 'display/', DI::args()->getQueryString()));
		}

		$item = Item::selectFirst(['id', 'uid', 'origin', 'author-link', 'changed', 'private', 'psid', 'gravity'],
			['guid' => $parameters['guid']], ['order' => ['origin' => true]]);

		if (!DBA::isResult($item)) {
			throw new HTTPException\NotFoundException();
		}

		$validated = in_array($item['private'], [Item::PUBLIC, Item::UNLISTED]);

		if (!$validated) {
			$requester = HTTPSignature::getSigner('', $_SERVER);
			if (!empty($requester) && $item['origin']) {
				$requester_id = Contact::getIdForURL($requester, $item['uid']);
				if (!empty($requester_id)) {
					$permissionSets = DI::permissionSet()->selectByContactId($requester_id, $item['uid']);
					if (!empty($permissionSets)) {
						$psid = array_merge($permissionSets->column('id'),
							[DI::permissionSet()->getIdFromACL($item['uid'], '', '', '', '')]);
						$validated = in_array($item['psid'], $psid);
					}
				}
			}
		}

		// Valid items are original post or posted from this node (including in the case of a forum)
		if (!$validated || !$item['origin'] && (parse_url($item['author-link'], PHP_URL_HOST) != parse_url(DI::baseUrl()->get(), PHP_URL_HOST))) {
			throw new HTTPException\NotFoundException();
		}

		$etag          = md5($parameters['guid'] . '-' . $item['changed']);
		$last_modified = $item['changed'];
		Network::checkEtagModified($etag, $last_modified);

		if (empty($parameters['activity']) && ($item['gravity'] != GRAVITY_ACTIVITY)) {
			$activity = ActivityPub\Transmitter::createActivityFromItem($item['id'], true);
			$activity['type'] = $activity['type'] == 'Update' ? 'Create' : $activity['type'];

			// Only display "Create" activity objects here, no reshares or anything else
			if (empty($activity['object']) || ($activity['type'] != 'Create')) {
				throw new HTTPException\NotFoundException();
			}

			$data = ['@context' => ActivityPub::CONTEXT];
			$data = array_merge($data, $activity['object']);
		} elseif (in_array($parameters['activity'], ['Create', 'Announce', 'Update', 
			'Like', 'Dislike', 'Accept', 'Reject', 'TentativeAccept', 'Follow', 'Add', ''])) {
			$data = ActivityPub\Transmitter::createActivityFromItem($item['id']);
			if (empty($data)) {
				throw new HTTPException\NotFoundException();
			}
			if (!in_array($parameters['activity'], ['Create', ''])) {
				$data['type'] = $parameters['activity'];
				$data['id'] = str_replace('/Create', '/' . $parameters['activity'], $data['id']);
			}
		} else {
			throw new HTTPException\NotFoundException();
		}

		// Relaxed CORS header for public items
		header('Access-Control-Allow-Origin: *');
		System::jsonExit($data, 'application/activity+json');
	}
}
