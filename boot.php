<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
 * Friendica is a communications platform for integrated social communications
 * utilising decentralised communications and linkage to several indie social
 * projects - as well as popular mainstream providers.
 *
 * Our mission is to free our friends and families from the clutches of
 * data-harvesting corporations, and pave the way to a future where social
 * communications are free and open and flow between alternate providers as
 * easily as email does today.
 */

use Friendica\Core\Session;

/**
 * @name Gravity
 *
 * Item weight for query ordering
 * @{
 */
define('GRAVITY_PARENT',       0);
define('GRAVITY_ACTIVITY',     3);
define('GRAVITY_COMMENT',      6);
define('GRAVITY_UNKNOWN',      9);
/* @}*/

/**
 * Returns the user id of locally logged in user or false.
 *
 * @return int|bool user id or false
 * @deprecated since version 2022.12, use Core\Session::getLocalUser() instead
 */
function local_user()
{
	return Session::getLocalUser();
}

/**
 * Returns the public contact id of logged in user or false.
 *
 * @return int|bool public contact id or false
 * @deprecated since version 2022.12, use Core\Session:: getPublicContact() instead
 */
function public_contact()
{
	return Session::getPublicContact();
}

/**
 * Returns public contact id of authenticated site visitor or false
 *
 * @return int|bool visitor_id or false
 * @deprecated since version 2022.12, use Core\Session:: getRemoteUser() instead
 */
function remote_user()
{
	return Session::getRemoteUser();
}
