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

namespace Friendica\Module\Api\Friendica\DirectMessages;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;

/**
 * search for direct_messages containing a searchstring through api
 *
 * API endpoint: api/friendica/direct_messages_search
 */
class Search extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'searchstring' => '',
		], $request);

		// error if no searchstring specified
		if ($request['searchstring'] == '') {
			$answer = ['result' => 'error', 'message' => 'searchstring not specified'];
			$this->response->exit('direct_message_search', ['$result' => $answer], $this->parameters['extension'] ?? null);
			exit;
		}

		// get data for the specified searchstring
		$mails = DBA::selectToArray('mail', ['id'], ["`uid` = ? AND `body` LIKE ?", $uid, '%' . $request['searchstring'] . '%'], ['order' => ['id' => true]]);

		// message if nothing was found
		if (!DBA::isResult($mails)) {
			$success = ['success' => false, 'search_results' => 'problem with query'];
		} elseif (count($mails) == 0) {
			$success = ['success' => false, 'search_results' => 'nothing found'];
		} else {
			$ret = [];
			foreach ($mails as $mail) {
				$ret[] = DI::twitterDirectMessage()->createFromMailId($mail['id'], $uid, $request['getText'] ?? '');
			}
			$success = ['success' => true, 'search_results' => $ret];
		}

		$this->response->exit('direct_message_search', ['$result' => $success], $this->parameters['extension'] ?? null);
	}
}
