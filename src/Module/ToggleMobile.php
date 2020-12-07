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
use Friendica\DI;

/**
 * Toggles the mobile view (on/off)
 */
class ToggleMobile extends BaseModule
{
	public static function content(array $parameters = [])
	{
		$a = DI::app();

		if (isset($_GET['off'])) {
			$_SESSION['show-mobile'] = false;
		} else {
			$_SESSION['show-mobile'] = true;
		}

		if (isset($_GET['address'])) {
			$address = $_GET['address'];
		} else {
			$address = '';
		}

		$a->redirect($address);
	}
}
