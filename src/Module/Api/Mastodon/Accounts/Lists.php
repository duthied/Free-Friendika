<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Module\Api\Mastodon\Accounts;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/
 */
class Lists extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$id = $this->parameters['id'];
		if (!DBA::exists('contact', ['id' => $id, 'uid' => 0])) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		$lists = [];

		$cdata = Contact::getPublicAndUserContactID($id, $uid);
		if (!empty($cdata['user'])) {
			$circles = DBA::select('group_member', ['gid'], ['contact-id' => $cdata['user']]);
			while ($circle = DBA::fetch($circles)) {
				$lists[] = DI::mstdnList()->createFromCircleId($circle['gid']);
			}
			DBA::close($circles);
		}

		$this->jsonExit($lists);
	}
}
