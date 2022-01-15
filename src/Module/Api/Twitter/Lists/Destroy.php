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
 * Delete a group.
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-destroy
 */
class Destroy extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();
	
		// params
		$gid = $_REQUEST['list_id'] ?? 0;
	
		// error if no gid specified
		if ($gid == 0) {
			throw new HTTPException\BadRequestException('gid not specified');
		}
	
		// get data of the specified group id
		$group = DBA::selectFirst('group', [], ['uid' => $uid, 'id' => $gid]);
		// error message if specified gid is not in database
		if (!$group) {
			throw new HTTPException\BadRequestException('gid not available');
		}
	
		$list = DI::friendicaGroup()->createFromId($gid);
	
		if (Group::remove($gid)) {
			$this->response->exit('statuses', ['lists' => ['lists' => $list]], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
		}
	}
}
