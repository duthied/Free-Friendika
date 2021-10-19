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

use Friendica\DI;

/**
 * Creates a notification entry and possibly sends a mail
 *
 * @param array $params Array with the elements:
 *                      type, event, otype, activity, verb, uid, cid, item, link,
 *                      source_name, source_mail, source_nick, source_link, source_photo,
 *                      show_in_notification_page
 *
 * @return bool
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function notification($params)
{
	return DI::notify()->createFromArray($params);
}
