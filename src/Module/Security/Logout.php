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

namespace Friendica\Module\Security;

use Friendica\BaseModule;
use Friendica\Core\Hook;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Profile;

/**
 * Logout module
 */
class Logout extends BaseModule
{
	/**
	 * Process logout requests
	 */
	public static function init(array $parameters = [])
	{
		$visitor_home = null;
		if (remote_user()) {
			$visitor_home = Profile::getMyURL();
			DI::cache()->delete('zrlInit:' . $visitor_home);
		}

		Hook::callAll("logging_out");
		DI::cookie()->clear();
		DI::session()->clear();

		if ($visitor_home) {
			System::externalRedirect($visitor_home);
		} else {
			info(DI::l10n()->t('Logged out.'));
			DI::baseUrl()->redirect();
		}
	}
}
