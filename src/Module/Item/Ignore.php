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

namespace Friendica\Module\Item;

use Friendica\BaseModule;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;

/**
 * Module for ignoring threads or user items
 */
class Ignore extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$l10n = DI::l10n();

		if (!Session::isAuthenticated()) {
			throw new HttpException\ForbiddenException($l10n->t('Access denied.'));
		}

		if (empty($parameters['id'])) {
			throw new HTTPException\BadRequestException();
		}

		$itemId = intval($parameters['id']);

		$dba = DI::dba();

		$thread = Post::selectFirstThreadForUser(local_user(), ['uid', 'ignored'], ['iid' => $itemId]);
		if (!$dba->isResult($thread)) {
			throw new HTTPException\NotFoundException();
		}

		// Numeric values are needed for the json output further below
		$ignored = !empty($thread['ignored']) ? 0 : 1;

		switch ($thread['uid'] ?? 0) {
			// if the thread is from the current user
			case local_user():
				$dba->update('thread', ['ignored' => $ignored], ['iid' => $itemId]);
				break;
			// 0 (null will get transformed to 0) => it's a public post
			case 0:
				$dba->update('user-item', ['ignored' => $ignored], ['iid' => $itemId, 'uid' => local_user()], true);
				break;
			// Throws a BadRequestException and not a ForbiddenException on purpose
			// Avoids harvesting existing, but forbidden IIDs (security issue)
			default:
				throw new HTTPException\BadRequestException();
		}

		// See if we've been passed a return path to redirect to
		$return_path = $_REQUEST['return'] ?? '';
		if (!empty($return_path)) {
			$rand = '_=' . time();
			if (strpos($return_path, '?')) {
				$rand = "&$rand";
			} else {
				$rand = "?$rand";
			}

			DI::baseUrl()->redirect($return_path . $rand);
		}

		$return = [
			'status'  => 'ok',
			'item_id' => $itemId,
			'verb'    => 'ignore',
			'state'   => $ignored,
		];

		System::jsonExit($return);
	}
}
