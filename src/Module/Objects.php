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
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Protocol\ActivityPub;

/**
 * ActivityPub Objects
 */
class Objects extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$a = DI::app();

		if (empty($a->argv[1])) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		if (!ActivityPub::isRequest()) {
			DI::baseUrl()->redirect(str_replace('objects/', 'display/', DI::args()->getQueryString()));
		}

		/// @todo Add Authentication to enable fetching of non public content
		// $requester = HTTPSignature::getSigner('', $_SERVER);

		// At first we try the original post with that guid
		// @TODO: Replace with parameter from router
		$item = Item::selectFirst(['id'], ['guid' => $a->argv[1], 'origin' => true, 'private' => [item::PUBLIC, Item::UNLISTED]]);
		if (!DBA::isResult($item)) {
			// If no original post could be found, it could possibly be a forum post, there we remove the "origin" field.
			// @TODO: Replace with parameter from router
			$item = Item::selectFirst(['id', 'author-link'], ['guid' => $a->argv[1], 'private' => [item::PUBLIC, Item::UNLISTED]]);
			if (!DBA::isResult($item) || !strstr($item['author-link'], DI::baseUrl()->get())) {
				throw new \Friendica\Network\HTTPException\NotFoundException();
			}
		}

		$activity = ActivityPub\Transmitter::createActivityFromItem($item['id'], true);
		$activity['type'] = $activity['type'] == 'Update' ? 'Create' : $activity['type'];

		// Only display "Create" activity objects here, no reshares or anything else
		if (empty($activity['object']) || ($activity['type'] != 'Create')) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$data = ['@context' => ActivityPub::CONTEXT];
		$data = array_merge($data, $activity['object']);

		header('Content-Type: application/activity+json');
		echo json_encode($data);
		exit();
	}
}
