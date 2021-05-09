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

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Database\DBA;

class Application extends BaseFactory
{
	/**
	 * @param int $id Application ID
	 */
	public function createFromApplicationId(int $id)
	{
		$application = DBA::selectFirst('application', ['client_id', 'client_secret', 'id', 'name', 'redirect_uri', 'website'], ['id' => $id]);
		if (!DBA::isResult($application)) {
			return [];
		}

		$object = new \Friendica\Object\Api\Mastodon\Application(
			$application['name'], 
			$application['client_id'], 
			$application['client_secret'], 
			$application['id'], 
			$application['redirect_uri'], 
			$application['website']);

		return $object->toArray();
	}
}
