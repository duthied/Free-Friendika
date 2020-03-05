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
use Friendica\Core\Session;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

/**
 * Performs a like and optionally redirects to a return path
 */
class Like extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		if (!Session::isAuthenticated()) {
			throw new HTTPException\ForbiddenException();
		}

		$verb = Strings::escapeTags(trim($_GET['verb']));

		if (!$verb) {
			$verb = 'like';
		}

		$app = DI::app();

		// @TODO: Replace with parameter from router
		$itemId = (($app->argc > 1) ? Strings::escapeTags(trim($app->argv[1])) : 0);

		if (!Item::performActivity($itemId, $verb)) {
			throw new HTTPException\BadRequestException();
		}

		// Decide how to return. If we were called with a 'return' argument,
		// then redirect back to the calling page. If not, just quietly end
		$returnPath = $_REQUEST['return'] ?? '';

		if (!empty($returnPath)) {
			$rand = '_=' . time();
			if (strpos($returnPath, '?')) {
				$rand = "&$rand";
			} else {
				$rand = "?$rand";
			}

			DI::baseUrl()->redirect($returnPath . $rand);
		}
	}
}
