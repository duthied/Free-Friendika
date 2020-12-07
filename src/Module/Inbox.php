<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Network;

/**
 * ActivityPub Inbox
 */
class Inbox extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$a = DI::app();

		$postdata = Network::postdata();

		if (empty($postdata)) {
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}

		if (DI::config()->get('debug', 'ap_inbox_log')) {
			if (HTTPSignature::getSigner($postdata, $_SERVER)) {
				$filename = 'signed-activitypub';
			} else {
				$filename = 'failed-activitypub';
			}
			$tempfile = tempnam(get_temppath(), $filename);
			file_put_contents($tempfile, json_encode(['argv' => $a->argv, 'header' => $_SERVER, 'body' => $postdata], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			Logger::log('Incoming message stored under ' . $tempfile);
		}

		// @TODO: Replace with parameter from router
		if (!empty($a->argv[1])) {
			$user = DBA::selectFirst('user', ['uid'], ['nickname' => $a->argv[1]]);
			if (!DBA::isResult($user)) {
				throw new \Friendica\Network\HTTPException\NotFoundException();
			}
			$uid = $user['uid'];
		} else {
			$uid = 0;
		}

		ActivityPub\Receiver::processInbox($postdata, $_SERVER, $uid);

		throw new \Friendica\Network\HTTPException\AcceptedException();
	}
}
