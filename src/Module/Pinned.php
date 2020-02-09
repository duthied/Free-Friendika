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
use Friendica\DI;
use Friendica\Model\Item;

/**
 * Toggle pinned items
 */
class Pinned extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		if (!local_user()) {
			throw new \Friendica\Network\HTTPException\ForbiddenException();
		}

		if (empty($parameters['item'])) {
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}

		$itemId = intval($parameters['item']);

		$pinned = !Item::getPinned($itemId, local_user());

		Item::setPinned($itemId, local_user(), $pinned);

		// See if we've been passed a return path to redirect to
		$returnPath = $_REQUEST['return'] ?? '';
		if (!empty($returnPath)) {
			$rand = '_=' . time() . (strpos($returnPath, '?') ? '&' : '?') . 'rand';
			DI::baseUrl()->redirect($returnPath . $rand);
		}

		// the json doesn't really matter, it will either be 0 or 1
		echo json_encode((int)$pinned);
		exit();
	}
}
