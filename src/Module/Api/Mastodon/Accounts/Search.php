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

namespace Friendica\Module\Api\Mastodon\Accounts;

use Friendica\Core\Search as CoreSearch;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\BaseApi;
use Friendica\Object\Search\ContactResult;
/**
 * @see https://docs.joinmastodon.org/methods/accounts/
 */
class Search extends BaseApi
{
	/**
	 * @param array $parameters
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function rawContent(array $parameters = [])
	{
		self::login();
		$uid = self::getCurrentUserID();

		// What to search for
		$q = (int)!isset($_REQUEST['q']) ? 0 : $_REQUEST['q'];
		// Maximum number of results. Defaults to 40.
		$limit = (int)!isset($_REQUEST['limit']) ? 40 : $_REQUEST['limit'];
		// Attempt WebFinger lookup. Defaults to false. Use this when q is an exact address.
		$resolve = (int)!isset($_REQUEST['resolve']) ? 0 : $_REQUEST['resolve'];
		// Only who the user is following. Defaults to false.
		$following = (int)!isset($_REQUEST['following']) ? 0 : $_REQUEST['following'];

		$accounts = [];

		if (!$following) {
			if ((strrpos($q, '@') > 0) && $resolve) {
				$results = CoreSearch::getContactsFromProbe($q);
			}

			if (empty($results)) {
				if (DI::config()->get('system', 'poco_local_search')) {
					$results = CoreSearch::getContactsFromLocalDirectory($q, CoreSearch::TYPE_ALL, 0, $limit);
				} elseif (!empty(DI::config()->get('system', 'directory'))) {
					$results = CoreSearch::getContactsFromGlobalDirectory($q, CoreSearch::TYPE_ALL, 1);
				}
			}

			if (!empty($results)) {
				$counter = 0;
				foreach ($results->getResults() as $result) {
					if (++$counter > $limit) {
						continue;
					}
					if ($result instanceof ContactResult) {
						$id = Contact::getIdForURL($result->getUrl(), 0, false);
						$accounts[] = DI::mstdnAccount()->createFromContactId($id, $uid);
					}
				}
			}
		} else {
			$contacts = Contact::searchByName($q, '', $uid);
			$counter = 0;
			foreach ($contacts as $contact) {
				if (!in_array($contact['rel'], [Contact::SHARING, Contact::FRIEND])) {
					continue;
				}
				if (++$counter > $limit) {
					continue;
				}
				$accounts[] = DI::mstdnAccount()->createFromContactId($contact['id'], $uid);
			}
			DBA::close($contacts);
		}

		System::jsonExit($accounts);
	}
}
