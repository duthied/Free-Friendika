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
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/
 */
class Accounts extends BaseApi
{
	/**
	 * @param array $parameters
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function rawContent(array $parameters = [])
	{
		if (empty($parameters['id']) && empty($parameters['name'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		if (!empty($parameters['id'])) {
			$id = $parameters['id'];
			if (!DBA::exists('contact', ['id' => $id, 'uid' => 0])) {
				DI::mstdnError()->RecordNotFound();
			}
		} else {
			$contact = Contact::selectFirst(['id'], ['nick' => $parameters['name'], 'uid' => 0]);
			if (!empty($contact['id'])) {
				$id = $contact['id'];
			} elseif (!($id = Contact::getIdForURL($parameters['name'], 0, false))) {
				DI::mstdnError()->RecordNotFound();
			}
		}

		$account = DI::mstdnAccount()->createFromContactId($id, self::getCurrentUserID());
		System::jsonExit($account);
	}
}
