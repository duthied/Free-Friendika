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

namespace Friendica\Module\Api\Mastodon\Accounts;

use Friendica\Core\Search as CoreSearch;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\BaseApi;
use Friendica\Object\Search\ContactResult;
use Friendica\Util\Network;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/
 */
class Search extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'q'         => '',    // What to search for
			'limit'     => 40,    // Maximum number of results. Defaults to 40.
			'resolve'   => false, // Attempt WebFinger lookup. Defaults to false. Use this when q is an exact address.
			'following' => false, // Only who the user is following. Defaults to false.
		], $request);

		$accounts = [];

		if ($request['resolve']) {
			if ((strrpos($request['q'], '@') > 0) || Network::isValidHttpUrl($request['q'])) {
				$results = CoreSearch::getContactsFromProbe($request['q']);
			}

			if (!empty($results)) {
				$counter = 0;
				foreach ($results->getResults() as $result) {
					if (++$counter > $request['limit']) {
						continue;
					}
					if ($result instanceof ContactResult) {
						$id = Contact::getIdForURL($result->getUrl(), 0, false);

						$accounts[] = DI::mstdnAccount()->createFromContactId($id, $uid);
					}
				}
			}
		}

		if (count($accounts) < $request['limit']) {
			$contacts = Contact::searchByName($request['q'], '', $request['following'] ? $uid : 0, $request['limit']);
			foreach ($contacts as $contact) {
				$accounts[] = DI::mstdnAccount()->createFromContactId($contact['id'], $uid);
			}
			DBA::close($contacts);
		}

		System::jsonExit($accounts);
	}
}
