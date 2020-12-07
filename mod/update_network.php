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
 * See update_profile.php for documentation
 */

use Friendica\App;
use Friendica\Core\System;
use Friendica\DI;

require_once "mod/network.php";

function update_network_content(App $a)
{
	if (!isset($_GET['p']) || !isset($_GET['item'])) {
		exit();
	}

	$profile_uid = intval($_GET['p']);
	$parent = intval($_GET['item']);

	if (!DI::pConfig()->get($profile_uid, "system", "no_auto_update") || ($_GET["force"] == 1)) {
		$text = network_content($a, $profile_uid, $parent);
	} else {
		$text = "";
	}
	System::htmlUpdateExit($text);
}
