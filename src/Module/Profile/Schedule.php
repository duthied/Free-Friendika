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

namespace Friendica\Module\Profile;

use Friendica\DI;
use Friendica\Module\BaseProfile;
use Friendica\Network\HTTPException;

class Schedule extends BaseProfile
{
	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		$a = DI::app();

		$o = self::getTabsHTML($a, 'schedule', true, $a->user);

		$o .= DI::l10n()->t('Currently here is no functionality here. Please use an app to have a look at your scheduled posts.');
		return $o;
	}
}
