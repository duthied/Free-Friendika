<?php

/*
 * @file src/Content/Widget/CalendarExport.php
 */

namespace Friendica\Content\Widget;

use Friendica\Core\L10n;

require_once 'boot.php';
require_once 'include/text.php';

/**
 * TagCloud widget
 *
 * @author Rabuzarus
 */
class CalendarExport
{
	/**
	 * @brief Get the events widget.
	 *
	 * @return string Formated HTML of the calendar widget.
	 */
	public static function getHTML() {
		$a = get_app();

//		$owner_uid = $a->data['user']['uid'];
//		// The permission testing is a little bit tricky because we have to respect many cases.
//
//		// It's not the private events page (we don't get the $owner_uid for /events).
//		if (! local_user() && ! $owner_uid) {
//			return;
//		}
//
//		/*
//		 * Cal logged in user (test permission at foreign profile page).
//		 * If the $owner uid is available we know it is part of one of the profile pages (like /cal).
//		 * So we have to test if if it's the own profile page of the logged in user
//		 * or a foreign one. For foreign profile pages we need to check if the feature
//		 * for exporting the cal is enabled (otherwise the widget would appear for logged in users
//		 * on foreigen profile pages even if the widget is disabled).
//		 */
//		if (intval($owner_uid) && local_user() !== $owner_uid && ! Feature::isEnabled($owner_uid, "export_calendar")) {
//			return;
//		}
//
//		/*
//		 * If it's a kind of profile page (intval($owner_uid)) return if the user not logged in and
//		 * export feature isn't enabled.
//		 */
//		if (intval($owner_uid) && ! local_user() && ! Feature::isEnabled($owner_uid, "export_calendar")) {
//			return;
//		}
		/*
		 * All the legacy checks above seem to be equivalent to the check below, see https://ethercalc.org/z6ehv1tut9cm
		 * If there is a mistake in the spreadsheet, please notify @MrPetovan on GitHub or by email mrpetovan@gmail.com
		 */
		if (!local_user()) {
			return;
		}

		// $a->data is only available if the profile page is visited. If the visited page is not part
		// of the profile page it should be the personal /events page. So we can use $a->user.
		$user = defaults($a->data['user'], 'nickname', $a->user['nickname']);

		$tpl = get_markup_template("events_aside.tpl");
		$return = replace_macros($tpl, [
			'$etitle'      => L10n::t("Export"),
			'$export_ical' => L10n::t("Export calendar as ical"),
			'$export_csv'  => L10n::t("Export calendar as csv"),
			'$user'        => $user
		]);

		return $return;
	}
}
