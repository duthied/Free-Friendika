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

namespace Friendica\Module\Api\Twitter\Account;

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile;

/**
 * Update user profile
 */
class UpdateProfile extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();

		$api_user = DI::twitterUser()->createFromUserId($uid, true)->toArray();

		if (!empty($_POST['name'])) {
			DBA::update('profile', ['name' => $_POST['name']], ['uid' => $uid]);
			DBA::update('user', ['username' => $_POST['name']], ['uid' => $uid]);
			Contact::update(['name' => $_POST['name']], ['uid' => $uid, 'self' => 1]);
			Contact::update(['name' => $_POST['name']], ['id' => $api_user['id']]);
		}

		if (isset($_POST['description'])) {
			DBA::update('profile', ['about' => $_POST['description']], ['uid' => $uid]);
			Contact::update(['about' => $_POST['description']], ['uid' => $uid, 'self' => 1]);
			Contact::update(['about' => $_POST['description']], ['id' => $api_user['id']]);
		}

		Profile::publishUpdate($uid);

		$skip_status = $_REQUEST['skip_status'] ?? false;

		$user_info = DI::twitterUser()->createFromUserId($uid, $skip_status)->toArray();

		// "verified" isn't used here in the standard
		unset($user_info["verified"]);

		// "uid" is only needed for some internal stuff, so remove it from here
		unset($user_info['uid']);

		$this->response->exit('user', ['user' => $user_info], $this->parameters['extension'] ?? null);
	}
}
