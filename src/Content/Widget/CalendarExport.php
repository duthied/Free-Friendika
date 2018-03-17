<?php

/*
 * @file src/Content/Widget/CalendarExport.php
 */

namespace Friendica\Content\Widget;

use Friendica\Content\Feature;
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

		$owner_uid = $a->data['user']['uid'];

		// The permission testing is a little bit tricky because we have to respect many cases.

		// It's not the private events page (we don't get the $owner_uid for /events).
		if (!local_user() && !$owner_uid) {
			return;
		}

		/*
		 * If it's a kind of profile page (intval($owner_uid)) return if the user not logged in and
		 * export feature isn't enabled.
		 */
		if (!local_user() && $owner_uid && !Feature::isEnabled($owner_uid, 'export_calendar')) {
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
