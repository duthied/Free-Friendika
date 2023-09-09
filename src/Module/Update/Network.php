<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Module\Update;

use Friendica\Content\Conversation;
use Friendica\Core\System;
use Friendica\Module\Conversation\Network as NetworkModule;

class Network extends NetworkModule
{
	protected function rawContent(array $request = [])
	{
		if (!isset($request['p']) || !isset($request['item'])) {
			System::exit();
		}

		$this->parseRequest($request);

		$profile_uid = intval($request['p']);

		$o = '';

		if (empty($request['force'])) {
			System::htmlUpdateExit($o);
		}

		$o = $this->conversation->render($this->getItems(), Conversation::MODE_NETWORK, $profile_uid, false, $this->getOrder(), $this->session->getLocalUserId());

		System::htmlUpdateExit($o);
	}
}
