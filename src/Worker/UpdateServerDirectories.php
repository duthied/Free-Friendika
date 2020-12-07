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

use Friendica\DI;
use Friendica\Model\GContact;
use Friendica\Model\GServer;
use Friendica\Protocol\PortableContact;

class UpdateServerDirectories
{
	/**
	 * Query global servers for their users
	 */
	public static function execute()
	{
		if (DI::config()->get('system', 'poco_discovery') == PortableContact::DISABLED) {
			return;
		}

		// Query Friendica and Hubzilla servers for their users
		GServer::discover();

		// Query GNU Social servers for their users ("statistics" addon has to be enabled on the GS server)
		if (!DI::config()->get('system', 'ostatus_disabled')) {
			GContact::discoverGsUsers();
		}
	}
}
