<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Module\Api\Mastodon\Conversation;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/conversations/
 */
class Read extends BaseApi
{
	public static function post(array $parameters = [])
	{
		self::login(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		if (!empty($parameters['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		DBA::update('mail', ['seen' => true], ['convid' => $parameters['id'], 'uid' => $uid]);

		System::jsonExit(DI::mstdnConversation()->CreateFromConvId($parameters['id'])->toArray());
	}
}
