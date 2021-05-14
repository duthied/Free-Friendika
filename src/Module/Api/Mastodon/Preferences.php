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

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/preferences/
 */
class Preferences extends BaseApi
{
	/**
	 * @param array $parameters
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function rawContent(array $parameters = [])
	{
		self::login();
		$uid = self::getCurrentUserID();

		$user = User::getById($uid, ['language', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid']);
		if (!empty($user['allow_cid']) || !empty($user['allow_gid']) || !empty($user['deny_cid']) || !empty($user['deny_gid'])) {
			$visibility = 'private';
		} elseif (DI::pConfig()->get($uid, 'system', 'unlisted')) {
			$visibility = 'unlisted';
		} else {
			$visibility = 'public';
		}

		$sensitive = false;
		$language  = $user['language'];
		$media     = DI::pConfig()->get($uid, 'nsfw', 'disable') ? 'show_all' : 'default';
		$spoilers  = DI::pConfig()->get($uid, 'system', 'disable_cw');

		$preferences = new \Friendica\Object\Api\Mastodon\Preferences($visibility, $sensitive, $language, $media, $spoilers);

		System::jsonExit($preferences);
	}
}
