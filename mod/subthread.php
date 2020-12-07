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

use Friendica\App;
use Friendica\Network\HTTPException;
use Friendica\Core\Logger;
use Friendica\Core\Session;
use Friendica\Model\Item;
use Friendica\Util\Strings;

function subthread_content(App $a)
{
	if (!Session::isAuthenticated()) {
		throw new HTTPException\ForbiddenException();
	}

	$item_id = (($a->argc > 1) ? Strings::escapeTags(trim($a->argv[1])) : 0);

	if (!Item::performActivity($item_id, 'follow')) {
		Logger::info('Following item failed', ['item' => $item_id]);
		throw new HTTPException\BadRequestException();
	}
	Logger::info('Followed item', ['item' => $item_id]);
	return;
}
