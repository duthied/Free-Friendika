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

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\DI;
use Friendica\Model\GContact;

class UpdateGContact
{
	/**
	 * Update global contact via probe
	 * @param string  $url     Global contact url
	 * @param string  $command
	 */
	public static function execute(string $url, string $command = '')
	{
		$force = ($command == "force");
		$nodiscover = ($command == "nodiscover");

		$success = GContact::updateFromProbe($url, $force);

		Logger::info('Updated from probe', ['url' => $url, 'force' => $force, 'success' => $success]);

		if ($success && !$nodiscover && (DI::config()->get('system', 'gcontact_discovery') == GContact::DISCOVERY_RECURSIVE)) {
			GContact::discoverFollowers($url);
		}
	}
}
