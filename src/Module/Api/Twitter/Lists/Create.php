<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Module\Api\Twitter\Lists;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Network\HTTPException;

/**
 * Update information about a group.
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-update
 */
class Create extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();
	
		// params
		$name = $_REQUEST['name'] ?? '';
	
		if ($name == '') {
			throw new HTTPException\BadRequestException('group name not specified');
		}
	
		// error message if specified group name already exists
		if (DBA::exists('group', ['uid' => $uid, 'name' => $name, 'deleted' => false])) {
			throw new HTTPException\BadRequestException('group name already exists');
		}
	
		$ret = Group::create($uid, $name);
		if ($ret) {
			$gid = Group::getIdByName($uid, $name);
		} else {
			throw new HTTPException\BadRequestException('other API error');
		}
	
		$grp = DI::friendicaGroup()->createFromId($gid);
	
		$this->response->exit('statuses', ['lists' => ['lists' => $grp]], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
