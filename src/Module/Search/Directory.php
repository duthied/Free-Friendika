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

namespace Friendica\Module\Search;

use Friendica\Content\Widget;
use Friendica\DI;
use Friendica\Module\BaseSearch;
use Friendica\Module\Security\Login;
use Friendica\Util\Strings;

/**
 * Directory search module
 */
class Directory extends BaseSearch
{
	public static function content(array $parameters = [])
	{
		if (!local_user()) {
			notice(DI::l10n()->t('Permission denied.'));
			return Login::form();
		}

		$search = Strings::escapeTags(trim(rawurldecode($_REQUEST['search'] ?? '')));

		if (empty(DI::page()['aside'])) {
			DI::page()['aside'] = '';
		}

		DI::page()['aside'] .= Widget::findPeople();
		DI::page()['aside'] .= Widget::follow();

		return self::performContactSearch($search);
	}
}
