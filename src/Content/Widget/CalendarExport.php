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

namespace Friendica\Content\Widget;

use Friendica\Content\Feature;
use Friendica\Core\Renderer;
use Friendica\DI;

/**
 * TagCloud widget
 *
 * @author Rabuzarus
 */
class CalendarExport
{
	/**
	 * Get the events widget.
	 *
	 * @return string Formated HTML of the calendar widget.
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getHTML() {
		$a = DI::app();

		if (empty($a->data['user'])) {
			return;
		}

		$owner_uid = intval($a->data['user']['uid']);

		// The permission testing is a little bit tricky because we have to respect many cases.

		// It's not the private events page (we don't get the $owner_uid for /events).
		if (!local_user() && !$owner_uid) {
			return;
		}

		// $a->data is only available if the profile page is visited. If the visited page is not part
		// of the profile page it should be the personal /events page. So we can use $a->user.
		$user = ($a->data['user']['nickname'] ?? '') ?: $a->user['nickname'];

		$tpl = Renderer::getMarkupTemplate("widget/events.tpl");
		$return = Renderer::replaceMacros($tpl, [
			'$etitle'      => DI::l10n()->t("Export"),
			'$export_ical' => DI::l10n()->t("Export calendar as ical"),
			'$export_csv'  => DI::l10n()->t("Export calendar as csv"),
			'$user'        => $user
		]);

		return $return;
	}
}
