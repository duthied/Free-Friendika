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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Protocol\ActivityPub;

/**
 * ActivityPub Following
 */
class Following extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$a = DI::app();

		// @TODO: Replace with parameter from router
		if (empty($a->argv[1])) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		// @TODO: Replace with parameter from router
		$owner = User::getOwnerDataByNick($a->argv[1]);
		if (empty($owner)) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$page = $_REQUEST['page'] ?? null;

		$following = ActivityPub\Transmitter::getContacts($owner, [Contact::SHARING, Contact::FRIEND], 'following', $page);

		header('Content-Type: application/activity+json');
		echo json_encode($following);
		exit();
	}
}
