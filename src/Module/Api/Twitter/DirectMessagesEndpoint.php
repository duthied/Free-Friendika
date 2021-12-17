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

namespace Friendica\Module\Api\Twitter;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\BaseApi;

abstract class DirectMessagesEndpoint extends BaseApi
{
	/**
	 * Handles a direct messages endpoint with the given condition
	 *
	 * @param array $request
	 * @param int   $uid
	 * @param array $condition
	 */
	protected function getMessages(array $request, int $uid, array $condition)
	{
		// params
		$count    = filter_var($request['count'] ?? 20,                FILTER_VALIDATE_INT, ['options' => ['max_range' => 100]]);
		$page     = filter_var($request['page'] ?? 1,                  FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		$since_id = filter_var($request['since_id'] ?? 0,              FILTER_VALIDATE_INT);
		$max_id   = filter_var($request['max_id'] ?? 0,                FILTER_VALIDATE_INT);
		$min_id   = filter_var($request['min_id'] ?? 0,                FILTER_VALIDATE_INT);
		$verbose  = filter_var($request['friendica_verbose'] ?? false, FILTER_VALIDATE_BOOLEAN);

		// pagination
		$start = max(0, ($page - 1) * $count);

		$params = ['order' => ['id' => true], 'limit' => [$start, $count]];

		if (!empty($max_id)) {
			$condition = DBA::mergeConditions($condition, ["`id` < ?", $max_id]);
		}

		if (!empty($since_id)) {
			$condition = DBA::mergeConditions($condition, ["`id` > ?", $since_id]);
		}

		if (!empty($min_id)) {
			$condition = DBA::mergeConditions($condition, ["`id` > ?", $min_id]);

			$params['order'] = ['id'];
		}

		$cid = BaseApi::getContactIDForSearchterm($_REQUEST['screen_name'] ?? '', $_REQUEST['profileurl'] ?? '', $_REQUEST['user_id'] ?? 0, 0);
		if (!empty($cid)) {
			$cdata = Contact::getPublicAndUserContactID($cid, $uid);
			if (!empty($cdata['user'])) {
				$condition = DBA::mergeConditions($condition, ["`contact-id` = ?", $cdata['user']]);
			}
		}

		$condition = DBA::mergeConditions($condition, ["`uid` = ?", $uid]);

		$mails = DBA::selectToArray('mail', ['id'], $condition, $params);
		if ($verbose && !DBA::isResult($mails)) {
			$answer = ['result' => 'error', 'message' => 'no mails available'];
			$this->response->exit('direct-messages', ['direct_message' => $answer], $this->parameters['extension'] ?? null);
			exit;
		}

		$ids = array_column($mails, 'id');

		if (!empty($min_id)) {
			$ids = array_reverse($ids);
		}

		$ret = [];
		foreach ($ids as $id) {
			$ret[] = DI::twitterDirectMessage()->createFromMailId($id, $uid, $request['getText'] ?? '');
		}

		self::setLinkHeader();

		$this->response->exit('direct-messages', ['direct_message' => $ret], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
