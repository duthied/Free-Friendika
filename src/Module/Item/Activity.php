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
use Friendica\Content\Text\BBCode;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

/**
 * Performs an activity (like, dislike, announce, attendyes, attendno, attendmaybe)
 * and optionally redirects to a return path
 */
class Activity extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		if (!Session::isAuthenticated()) {
			throw new HTTPException\ForbiddenException();
		}

		if (empty($parameters['id']) || empty($parameters['verb'])) {
			throw new HTTPException\BadRequestException();
		}

		$verb = $parameters['verb'];
		$itemId =  $parameters['id'];

		if (in_array($verb, ['announce', 'unannounce'])) {
			$item = Post::selectFirst(['network'], ['id' => $itemId]);
			if ($item['network'] == Protocol::DIASPORA) {
				self::performDiasporaReshare($itemId);
			}
		}

		if (!Item::performActivity($itemId, $verb, local_user())) {
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
			'status' => 'ok',
			'item_id' => $itemId,
			'verb' => $verb,
			'state' => 1,
		];

		System::jsonExit($return);
	}

	private static function performDiasporaReshare(int $itemId)
	{
		$fields = ['uri-id', 'body', 'title', 'author-name', 'author-link', 'author-avatar', 'guid', 'created', 'plink'];
		$item = Post::selectFirst($fields, ['id' => $itemId, 'private' => [Item::PUBLIC, Item::UNLISTED]]);
		if (!DBA::isResult($item) || ($item['body'] == '')) {
			return;
		}

		if (strpos($item['body'], '[/share]') !== false) {
			$pos = strpos($item['body'], '[share');
			$post = substr($item['body'], $pos);
		} else {
			$post = BBCode::getShareOpeningTag($item['author-name'], $item['author-link'], $item['author-avatar'], $item['plink'], $item['created'], $item['guid']);

			if (!empty($item['title'])) {
				$post .= '[h3]' . $item['title'] . "[/h3]\n";
			}

			$post .= $item['body'];
			$post .= '[/share]';
		}
		$_REQUEST['body'] = $post;
		$_REQUEST['profile_uid'] = local_user();

		require_once 'mod/item.php';
		item_post(DI::app());
	}
}
