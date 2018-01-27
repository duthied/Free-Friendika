<?php
/**
 * @file include/event.php
 * @brief functions specific to event handling
 */

use Friendica\Content\Feature;
use Friendica\Core\Addon;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Item;
use Friendica\Model\Profile;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Map;

require_once 'include/bbcode.php';
require_once 'include/datetime.php';
require_once 'include/conversation.php';

function format_event_html($ev, $simple = false) {
	if (! ((is_array($ev)) && count($ev))) {
		return '';
	}

	$bd_format = L10n::t('l F d, Y \@ g:i A') ; // Friday January 18, 2011 @ 8 AM.

	$event_start = day_translate(
		$ev['adjust'] ?
			DateTimeFormat::local($ev['start'], $bd_format)
			: DateTimeFormat::utc($ev['start'], $bd_format)
	);

	$event_end = day_translate(
		$ev['adjust'] ?
			DateTimeFormat::local($ev['finish'], $bd_format)
			: DateTimeFormat::utc($ev['finish'], $bd_format)
	);

	if ($simple) {
		$o = "<h3>" . bbcode($ev['summary']) . "</h3>";

		$o .= "<div>" . bbcode($ev['desc']) . "</div>";

		$o .= "<h4>" . L10n::t('Starts:') . "</h4><p>" . $event_start . "</p>";

		if (! $ev['nofinish']) {
			$o .= "<h4>" . L10n::t('Finishes:') . "</h4><p>" . $event_end  ."</p>";
		}

		if (strlen($ev['location'])) {
			$o .= "<h4>" . L10n::t('Location:') . "</h4><p>" . $ev['location'] . "</p>";
		}

		return $o;
	}

	$o = '<div class="vevent">' . "\r\n";

	$o .= '<div class="summary event-summary">' . bbcode($ev['summary']) . '</div>' . "\r\n";

	$o .= '<div class="event-start"><span class="event-label">' . L10n::t('Starts:') . '</span>&nbsp;<span class="dtstart" title="'
		. DateTimeFormat::utc($ev['start'], (($ev['adjust']) ? DateTimeFormat::ATOM : 'Y-m-d\TH:i:s' ))
		. '" >'.$event_start
		. '</span></div>' . "\r\n";

	if (! $ev['nofinish']) {
		$o .= '<div class="event-end" ><span class="event-label">' . L10n::t('Finishes:') . '</span>&nbsp;<span class="dtend" title="'
			. DateTimeFormat::utc($ev['finish'], (($ev['adjust']) ? DateTimeFormat::ATOM : 'Y-m-d\TH:i:s' ))
			. '" >'.$event_end
			. '</span></div>' . "\r\n";
	}

	$o .= '<div class="description event-description">' . bbcode($ev['desc']) . '</div>' . "\r\n";

	if (strlen($ev['location'])) {
		$o .= '<div class="event-location"><span class="event-label">' . L10n::t('Location:') . '</span>&nbsp;<span class="location">'
			. bbcode($ev['location'])
			. '</span></div>' . "\r\n";

		// Include a map of the location if the [map] BBCode is used.
		if (strpos($ev['location'], "[map") !== false) {
			$map = Map::byLocation($ev['location']);
			if ($map !== $ev['location']) {
				$o.= $map;
			}
		}
	}

	$o .= '</div>' . "\r\n";
	return $o;
}

/**
 * @brief Convert an array with event data to bbcode.
 *
 * @param array $ev Array which conains the event data.
 * @return string The event as a bbcode formatted string.
 */
function format_event_bbcode($ev) {

	$o = '';

	if ($ev['summary']) {
		$o .= '[event-summary]' . $ev['summary'] . '[/event-summary]';
	}

	if ($ev['desc']) {
		$o .= '[event-description]' . $ev['desc'] . '[/event-description]';
	}

	if ($ev['start']) {
		$o .= '[event-start]' . $ev['start'] . '[/event-start]';
	}

	if (($ev['finish']) && (! $ev['nofinish'])) {
		$o .= '[event-finish]' . $ev['finish'] . '[/event-finish]';
	}

	if ($ev['location']) {
		$o .= '[event-location]' . $ev['location'] . '[/event-location]';
	}

	if ($ev['adjust']) {
		$o .= '[event-adjust]' . $ev['adjust'] . '[/event-adjust]';
	}

	return $o;
}

/**
 * @brief Extract bbcode formatted event data from a string
 *     and convert it to html.
 *
 * @params: string $s The string which should be parsed for event data.
 * @return string The html output.
 */
function bbtovcal($s) {
	$o = '';
	$ev = bbtoevent($s);

	if ($ev['desc']) {
		$o = format_event_html($ev);
	}

	return $o;
}

/**
 * @brief Extract bbcode formatted event data from a string.
 *
 * @params: string $s The string which should be parsed for event data.
 * @return array The array with the event information.
 */
function bbtoevent($s) {

	$ev = [];

	$match = '';
	if (preg_match("/\[event\-summary\](.*?)\[\/event\-summary\]/is", $s, $match)) {
		$ev['summary'] = $match[1];
	}

	$match = '';
	if (preg_match("/\[event\-description\](.*?)\[\/event\-description\]/is", $s, $match)) {
		$ev['desc'] = $match[1];
	}

	$match = '';
	if (preg_match("/\[event\-start\](.*?)\[\/event\-start\]/is", $s, $match)) {
		$ev['start'] = $match[1];
	}

	$match = '';
	if (preg_match("/\[event\-finish\](.*?)\[\/event\-finish\]/is", $s, $match)) {
		$ev['finish'] = $match[1];
	}

	$match = '';
	if (preg_match("/\[event\-location\](.*?)\[\/event\-location\]/is", $s, $match)) {
		$ev['location'] = $match[1];
	}

	$match = '';
	if (preg_match("/\[event\-adjust\](.*?)\[\/event\-adjust\]/is", $s, $match)) {
		$ev['adjust'] = $match[1];
	}

	$ev['nofinish'] = (((x($ev, 'start') && $ev['start']) && (!x($ev, 'finish') || !$ev['finish'])) ? 1 : 0);

	return $ev;
}

function sort_by_date($a) {

	usort($a,'ev_compare');
	return $a;
}

function ev_compare($a,$b) {

	$date_a = (($a['adjust']) ? DateTimeFormat::local($a['start']) : $a['start']);
	$date_b = (($b['adjust']) ? DateTimeFormat::local($b['start']) : $b['start']);

	if ($date_a === $date_b) {
		return strcasecmp($a['desc'], $b['desc']);
	}

	return strcmp($date_a, $date_b);
}

/**
 * @brief Delete an event from the event table.
 *
 * Note: This function does only delete the event from the event table not its
 * related entry in the item table.
 *
 * @param int $event_id Event ID.
 * @return void
 */
function event_delete($event_id) {
	if ($event_id == 0) {
		return;
	}

	dba::delete('event', ['id' => $event_id]);
	logger("Deleted event ".$event_id, LOGGER_DEBUG);
}

/**
 * @brief Store the event.
 *
 * Store the event in the event table and create an event item in the item table.
 *
 * @param array $arr Array with event data.
 * @return int The event id.
 */
function event_store($arr) {

	require_once 'include/datetime.php';
	require_once 'include/items.php';
	require_once 'include/bbcode.php';

	$a = get_app();

	$arr['created'] = (($arr['created'])     ? $arr['created']         : DateTimeFormat::utcNow());
	$arr['edited']  = (($arr['edited'])      ? $arr['edited']          : DateTimeFormat::utcNow());
	$arr['type']    = (($arr['type'])        ? $arr['type']            : 'event' );
	$arr['cid']     = ((intval($arr['cid'])) ? intval($arr['cid'])     : 0);
	$arr['uri']     = (x($arr, 'uri')        ? $arr['uri']             : item_new_uri($a->get_hostname(), $arr['uid']));
	$arr['private'] = ((x($arr, 'private'))  ? intval($arr['private']) : 0);
	$arr['guid']    = get_guid(32);

	if ($arr['cid']) {
		$c = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($arr['cid']),
			intval($arr['uid'])
		);
	} else {
		$c = q("SELECT * FROM `contact` WHERE `self` = 1 AND `uid` = %d LIMIT 1",
			intval($arr['uid'])
		);
	}

	if (DBM::is_result($c)) {
		$contact = $c[0];
	}

	// Existing event being modified.
	if ($arr['id']) {

		// has the event actually changed?
		$r = q("SELECT * FROM `event` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($arr['id']),
			intval($arr['uid'])
		);
		if ((! DBM::is_result($r)) || ($r[0]['edited'] === $arr['edited'])) {

			// Nothing has changed. Grab the item id to return.
			$r = q("SELECT * FROM `item` WHERE `event-id` = %d AND `uid` = %d LIMIT 1",
				intval($arr['id']),
				intval($arr['uid'])
			);
			return ((DBM::is_result($r)) ? $r[0]['id'] : 0);
		}

		// The event changed. Update it.
		q("UPDATE `event` SET
			`edited` = '%s',
			`start` = '%s',
			`finish` = '%s',
			`summary` = '%s',
			`desc` = '%s',
			`location` = '%s',
			`type` = '%s',
			`adjust` = %d,
			`nofinish` = %d
			WHERE `id` = %d AND `uid` = %d",

			dbesc($arr['edited']),
			dbesc($arr['start']),
			dbesc($arr['finish']),
			dbesc($arr['summary']),
			dbesc($arr['desc']),
			dbesc($arr['location']),
			dbesc($arr['type']),
			intval($arr['adjust']),
			intval($arr['nofinish']),
			intval($arr['id']),
			intval($arr['uid'])
		);

		$r = q("SELECT * FROM `item` WHERE `event-id` = %d AND `uid` = %d LIMIT 1",
			intval($arr['id']),
			intval($arr['uid'])
		);
		if (DBM::is_result($r)) {
			$object = '<object><type>' . xmlify(ACTIVITY_OBJ_EVENT) . '</type><title></title><id>' . xmlify($arr['uri']) . '</id>';
			$object .= '<content>' . xmlify(format_event_bbcode($arr)) . '</content>';
			$object .= '</object>' . "\n";

			q("UPDATE `item` SET `body` = '%s', `object` = '%s', `edited` = '%s' WHERE `id` = %d AND `uid` = %d",
				dbesc(format_event_bbcode($arr)),
				dbesc($object),
				dbesc($arr['edited']),
				intval($r[0]['id']),
				intval($arr['uid'])
			);

			$item_id = $r[0]['id'];
		} else {
			$item_id = 0;
		}

		Addon::callHooks("event_updated", $arr['id']);

		return $item_id;
	} else {
		// New event. Store it.
		q("INSERT INTO `event` (`uid`,`cid`,`guid`,`uri`,`created`,`edited`,`start`,`finish`,`summary`, `desc`,`location`,`type`,
			`adjust`,`nofinish`,`allow_cid`,`allow_gid`,`deny_cid`,`deny_gid`)
			VALUES ( %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s' ) ",
			intval($arr['uid']),
			intval($arr['cid']),
			dbesc($arr['guid']),
			dbesc($arr['uri']),
			dbesc($arr['created']),
			dbesc($arr['edited']),
			dbesc($arr['start']),
			dbesc($arr['finish']),
			dbesc($arr['summary']),
			dbesc($arr['desc']),
			dbesc($arr['location']),
			dbesc($arr['type']),
			intval($arr['adjust']),
			intval($arr['nofinish']),
			dbesc($arr['allow_cid']),
			dbesc($arr['allow_gid']),
			dbesc($arr['deny_cid']),
			dbesc($arr['deny_gid'])
		);

		$r = q("SELECT * FROM `event` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
			dbesc($arr['uri']),
			intval($arr['uid'])
		);
		if (DBM::is_result($r)) {
			$event = $r[0];
		}

		$item_arr = [];

		$item_arr['uid']           = $arr['uid'];
		$item_arr['contact-id']    = $arr['cid'];
		$item_arr['uri']           = $arr['uri'];
		$item_arr['parent-uri']    = $arr['uri'];
		$item_arr['guid']          = $arr['guid'];
		$item_arr['type']          = 'activity';
		$item_arr['wall']          = (($arr['cid']) ? 0 : 1);
		$item_arr['contact-id']    = $contact['id'];
		$item_arr['owner-name']    = $contact['name'];
		$item_arr['owner-link']    = $contact['url'];
		$item_arr['owner-avatar']  = $contact['thumb'];
		$item_arr['author-name']   = $contact['name'];
		$item_arr['author-link']   = $contact['url'];
		$item_arr['author-avatar'] = $contact['thumb'];
		$item_arr['title']         = '';
		$item_arr['allow_cid']     = $arr['allow_cid'];
		$item_arr['allow_gid']     = $arr['allow_gid'];
		$item_arr['deny_cid']      = $arr['deny_cid'];
		$item_arr['deny_gid']      = $arr['deny_gid'];
		$item_arr['private']       = $arr['private'];
		$item_arr['visible']       = 1;
		$item_arr['verb']          = ACTIVITY_POST;
		$item_arr['object-type']   = ACTIVITY_OBJ_EVENT;
		$item_arr['origin']        = ((intval($arr['cid']) == 0) ? 1 : 0);
		$item_arr['body']          = format_event_bbcode($event);


		$item_arr['object']  = '<object><type>' . xmlify(ACTIVITY_OBJ_EVENT) . '</type><title></title><id>' . xmlify($arr['uri']) . '</id>';
		$item_arr['object'] .= '<content>' . xmlify(format_event_bbcode($event)) . '</content>';
		$item_arr['object'] .= '</object>' . "\n";

		$item_id = Item::insert($item_arr);
		if ($item_id) {
			q("UPDATE `item` SET `event-id` = %d  WHERE `uid` = %d AND `id` = %d",
				intval($event['id']),
				intval($arr['uid']),
				intval($item_id)
			);
		}

		Addon::callHooks("event_created", $event['id']);

		return $item_id;
	}
}

/**
 * @brief Create an array with translation strings used for events.
 *
 * @return array Array with translations strings.
 */
function get_event_strings() {

	// First day of the week (0 = Sunday).
	$firstDay = PConfig::get(local_user(), 'system', 'first_day_of_week', 0);

	$i18n = [
			"firstDay" => $firstDay,
			"allday"   => L10n::t("all-day"),

			"Sun" => L10n::t("Sun"),
			"Mon" => L10n::t("Mon"),
			"Tue" => L10n::t("Tue"),
			"Wed" => L10n::t("Wed"),
			"Thu" => L10n::t("Thu"),
			"Fri" => L10n::t("Fri"),
			"Sat" => L10n::t("Sat"),

			"Sunday"    => L10n::t("Sunday"),
			"Monday"    => L10n::t("Monday"),
			"Tuesday"   => L10n::t("Tuesday"),
			"Wednesday" => L10n::t("Wednesday"),
			"Thursday"  => L10n::t("Thursday"),
			"Friday"    => L10n::t("Friday"),
			"Saturday"  => L10n::t("Saturday"),

			"Jan" => L10n::t("Jan"),
			"Feb" => L10n::t("Feb"),
			"Mar" => L10n::t("Mar"),
			"Apr" => L10n::t("Apr"),
			"May" => L10n::t("May"),
			"Jun" => L10n::t("Jun"),
			"Jul" => L10n::t("Jul"),
			"Aug" => L10n::t("Aug"),
			"Sep" => L10n::t("Sept"),
			"Oct" => L10n::t("Oct"),
			"Nov" => L10n::t("Nov"),
			"Dec" => L10n::t("Dec"),

			"January"   => L10n::t("January"),
			"February"  => L10n::t("February"),
			"March"     => L10n::t("March"),
			"April"     => L10n::t("April"),
			"May"       => L10n::t("May"),
			"June"      => L10n::t("June"),
			"July"      => L10n::t("July"),
			"August"    => L10n::t("August"),
			"September" => L10n::t("September"),
			"October"   => L10n::t("October"),
			"November"  => L10n::t("November"),
			"December"  => L10n::t("December"),

			"today" => L10n::t("today"),
			"month" => L10n::t("month"),
			"week"  => L10n::t("week"),
			"day"   => L10n::t("day"),

			"noevent" => L10n::t("No events to display"),

			"dtstart_label"  => L10n::t("Starts:"),
			"dtend_label"    => L10n::t("Finishes:"),
			"location_label" => L10n::t("Location:")
		];

	return $i18n;
}

/**
 * @brief Removes duplicated birthday events.
 *
 * @param array $dates Array of possibly duplicated events.
 * @return array Cleaned events.
 *
 * @todo We should replace this with a separate update function if there is some time left.
 */
function event_remove_duplicates($dates) {
	$dates2 = [];

	foreach ($dates as $date) {
		if ($date['type'] == 'birthday') {
			$dates2[$date['uid'] . "-" . $date['cid'] . "-" . $date['start']] = $date;
		} else {
			$dates2[] = $date;
		}
	}
	return $dates2;
}

/**
 * @brief Get an event by its event ID.
 *
 * @param int    $owner_uid    The User ID of the owner of the event
 * @param array  $event_params An assoziative array with
 *	                           int 'event_id' => The ID of the event in the event table
 * @param string $sql_extra
 * @return array Query result
 */
function event_by_id($owner_uid = 0, $event_params, $sql_extra = '') {
	// Ownly allow events if there is a valid owner_id.
	if ($owner_uid == 0) {
		return;
	}

	// Query for the event by event id
	$r = q("SELECT `event`.*, `item`.`id` AS `itemid`,`item`.`plink`,
			`item`.`author-name`, `item`.`author-avatar`, `item`.`author-link` FROM `event`
		LEFT JOIN `item` ON `item`.`event-id` = `event`.`id` AND `item`.`uid` = `event`.`uid`
		WHERE `event`.`uid` = %d AND `event`.`id` = %d $sql_extra",
		intval($owner_uid),
		intval($event_params["event_id"])
	);

	if (DBM::is_result($r)) {
		return event_remove_duplicates($r);
	}
}

/**
 * @brief Get all events in a specific timeframe.
 *
 * @param int $owner_uid The User ID of the owner of the events.
 * @param array $event_params An assoziative array with
 *	int 'ignored' =><br>
 *	string 'start' => Start time of the timeframe.<br>
 *	string 'finish' => Finish time of the timeframe.<br>
 *	string 'adjust_start' =><br>
 *	string 'adjust_start' =>
 *
 * @param string $sql_extra Additional sql conditions (e.g. permission request).
 *
 * @return array Query results.
 */
function events_by_date($owner_uid = 0, $event_params, $sql_extra = '') {
	// Only allow events if there is a valid owner_id.
	if ($owner_uid == 0) {
		return;
	}

	// Query for the event by date.
	$r = q("SELECT `event`.*, `item`.`id` AS `itemid`,`item`.`plink`,
				`item`.`author-name`, `item`.`author-avatar`, `item`.`author-link` FROM `event`
			LEFT JOIN `item` ON `item`.`event-id` = `event`.`id` AND `item`.`uid` = `event`.`uid`
			WHERE `event`.`uid` = %d AND event.ignore = %d
			AND ((`adjust` = 0 AND (`finish` >= '%s' OR (nofinish AND start >= '%s')) AND `start` <= '%s')
			OR  (`adjust` = 1 AND (`finish` >= '%s' OR (nofinish AND start >= '%s')) AND `start` <= '%s'))
			$sql_extra ",
			intval($owner_uid),
			intval($event_params["ignored"]),
			dbesc($event_params["start"]),
			dbesc($event_params["start"]),
			dbesc($event_params["finish"]),
			dbesc($event_params["adjust_start"]),
			dbesc($event_params["adjust_start"]),
			dbesc($event_params["adjust_finish"])
	);

	if (DBM::is_result($r)) {
		return event_remove_duplicates($r);
	}
}

/**
 * @brief Convert an array query results in an arry which could be used by the events template.
 *
 * @param array $arr Event query array.
 * @return array Event array for the template.
 */
function process_events($arr) {
	$events=[];

	$last_date = '';
	$fmt = L10n::t('l, F j');
	if (count($arr)) {
		foreach ($arr as $rr) {
			$j = (($rr['adjust']) ? DateTimeFormat::local($rr['start'], 'j') : DateTimeFormat::utc($rr['start'], 'j'));
			$d = (($rr['adjust']) ? DateTimeFormat::local($rr['start'], $fmt) : DateTimeFormat::utc($rr['start'], $fmt));
			$d = day_translate($d);

			$start = (($rr['adjust']) ? DateTimeFormat::local($rr['start'], 'c') : DateTimeFormat::utc($rr['start'], 'c'));
			if ($rr['nofinish']) {
				$end = null;
			} else {
				$end = (($rr['adjust']) ? DateTimeFormat::local($rr['finish'], 'c') : DateTimeFormat::utc($rr['finish'], 'c'));
			}

			$is_first = ($d !== $last_date);

			$last_date = $d;

			// Show edit and drop actions only if the user is the owner of the event and the event
			// is a real event (no bithdays).
			$edit = null;
			$copy = null;
			$drop = null;
			if (local_user() && local_user() == $rr['uid'] && $rr['type'] == 'event') {
				$edit = ((! $rr['cid']) ? [System::baseUrl() . '/events/event/' . $rr['id'], L10n::t('Edit event'), '', ''] : null);
				$copy = ((! $rr['cid']) ? [System::baseUrl() . '/events/copy/' . $rr['id'], L10n::t('Duplicate event'), '', ''] : null);
				$drop = [System::baseUrl() . '/events/drop/' . $rr['id'], L10n::t('Delete event'), '', ''];
			}

			$title = strip_tags(html_entity_decode(bbcode($rr['summary']), ENT_QUOTES, 'UTF-8'));
			if (! $title) {
				list($title, $_trash) = explode("<br", bbcode($rr['desc']), 2);
				$title = strip_tags(html_entity_decode($title, ENT_QUOTES, 'UTF-8'));
			}

			$html = format_event_html($rr);
			$rr['desc'] = bbcode($rr['desc']);
			$rr['location'] = bbcode($rr['location']);
			$events[] = [
				'id'     => $rr['id'],
				'start'  => $start,
				'end'    => $end,
				'allDay' => false,
				'title'  => $title,

				'j'        => $j,
				'd'        => $d,
				'edit'     => $edit,
				'drop'     => $drop,
				'copy'     => $copy,
				'is_first' => $is_first,
				'item'     => $rr,
				'html'     => $html,
				'plink'    => [$rr['plink'], L10n::t('link to source'), '', ''],
			];
		}
	}

	return $events;
}

/**
 * @brief Format event to export format (ical/csv).
 *
 * @param array $events Query result for events.
 * @param string $format The output format (ical/csv).
 * @param string $timezone The timezone of the user (not implemented yet).
 *
 * @return string Content according to selected export format.
 *
 * @todo Implement timezone support
 */
function event_format_export($events, $format = 'ical', $timezone)
{
	if (!((is_array($events)) && count($events))) {
		return;
	}

	switch ($format) {
		// Format the exported data as a CSV file.
		case "csv":
			header("Content-type: text/csv");
			$o = '"Subject", "Start Date", "Start Time", "Description", "End Date", "End Time", "Location"' . PHP_EOL;

			foreach ($events as $event) {
				/// @todo The time / date entries don't include any information about the
				/// timezone the event is scheduled in :-/
				$tmp1 = strtotime($event['start']);
				$tmp2 = strtotime($event['finish']);
				$time_format = "%H:%M:%S";
				$date_format = "%Y-%m-%d";

				$o .= '"' . $event['summary'] . '", "' . strftime($date_format, $tmp1) .
					'", "' . strftime($time_format, $tmp1) . '", "' . $event['desc'] .
					'", "' . strftime($date_format, $tmp2) .
					'", "' . strftime($time_format, $tmp2) .
					'", "' . $event['location'] . '"' . PHP_EOL;
			}
			break;

		// Format the exported data as a ics file.
		case "ical":
			header("Content-type: text/ics");
			$o = 'BEGIN:VCALENDAR' . PHP_EOL
				. 'VERSION:2.0' . PHP_EOL
				. 'PRODID:-//friendica calendar export//0.1//EN' . PHP_EOL;
			///  @todo include timezone informations in cases were the time is not in UTC
			//  see http://tools.ietf.org/html/rfc2445#section-4.8.3
			//		. 'BEGIN:VTIMEZONE' . PHP_EOL
			//		. 'TZID:' . $timezone . PHP_EOL
			//		. 'END:VTIMEZONE' . PHP_EOL;
			//  TODO instead of PHP_EOL CRLF should be used for long entries
			//       but test your solution against http://icalvalid.cloudapp.net/
			//       also long lines SHOULD be split at 75 characters length
			foreach ($events as $event) {
				if ($event['adjust'] == 1) {
					$UTC = 'Z';
				} else {
					$UTC = '';
				}
				$o .= 'BEGIN:VEVENT' . PHP_EOL;

				if ($event['start']) {
					$tmp = strtotime($event['start']);
					$dtformat = "%Y%m%dT%H%M%S" . $UTC;
					$o .= 'DTSTART:' . strftime($dtformat, $tmp) . PHP_EOL;
				}

				if (!$event['nofinish']) {
					$tmp = strtotime($event['finish']);
					$dtformat = "%Y%m%dT%H%M%S" . $UTC;
					$o .= 'DTEND:' . strftime($dtformat, $tmp) . PHP_EOL;
				}

				if ($event['summary']) {
					$tmp = $event['summary'];
					$tmp = str_replace(PHP_EOL, PHP_EOL . ' ', $tmp);
					$tmp = addcslashes($tmp, ',;');
					$o .= 'SUMMARY:' . $tmp . PHP_EOL;
				}

				if ($event['desc']) {
					$tmp = $event['desc'];
					$tmp = str_replace(PHP_EOL, PHP_EOL . ' ', $tmp);
					$tmp = addcslashes($tmp, ',;');
					$o .= 'DESCRIPTION:' . $tmp . PHP_EOL;
				}

				if ($event['location']) {
					$tmp = $event['location'];
					$tmp = str_replace(PHP_EOL, PHP_EOL . ' ', $tmp);
					$tmp = addcslashes($tmp, ',;');
					$o .= 'LOCATION:' . $tmp . PHP_EOL;
				}

				$o .= 'END:VEVENT' . PHP_EOL;
				$o .= PHP_EOL;
			}

			$o .= 'END:VCALENDAR' . PHP_EOL;
			break;
	}

	return $o;
}

/**
 * @brief Get all events for a user ID.
 *
 *    The query for events is done permission sensitive.
 *    If the user is the owner of the calendar he/she
 *    will get all of his/her available events.
 *    If the user is only a visitor only the public events will
 *    be available.
 *
 * @param int $uid The user ID.
 * @param int $sql_extra Additional sql conditions for permission.
 *
 * @return array Query results.
 */
function events_by_uid($uid = 0, $sql_extra = '') {
	if ($uid == 0) {
		return;
	}

	// The permission condition if no condition was transmitted.
	if ($sql_extra == '') {
		$sql_extra = " AND `allow_cid` = '' AND `allow_gid` = '' ";
	}

	// Does the user who requests happen to be the owner of the events
	// requested? then show all of your events, otherwise only those that
	// don't have limitations set in allow_cid and allow_gid.
	if (local_user() == $uid) {
		$r = q("SELECT `start`, `finish`, `adjust`, `summary`, `desc`, `location`, `nofinish`
			FROM `event` WHERE `uid`= %d AND `cid` = 0 ",
			intval($uid)
		);
	} else {
		$r = q("SELECT `start`, `finish`, `adjust`, `summary`, `desc`, `location`, `nofinish`
			FROM `event` WHERE `uid`= %d AND `cid` = 0 $sql_extra ",
			intval($uid)
		);
	}

	if (DBM::is_result($r)) {
		return $r;
	}
}

/**
 *
 * @param int $uid The user ID.
 * @param string $format Output format (ical/csv).
 * @return array With the results:
 *	bool 'success' => True if the processing was successful,<br>
 *	string 'format' => The output format,<br>
 *	string 'extension' => The file extension of the output format,<br>
 *	string 'content' => The formatted output content.<br>
 *
 * @todo Respect authenticated users with events_by_uid().
 */
function event_export($uid, $format = 'ical') {

	$process = false;

	// We are allowed to show events.
	// Get the timezone the user is in.
	$r = q("SELECT `timezone` FROM `user` WHERE `uid` = %d LIMIT 1", intval($uid));
	if (DBM::is_result($r)) {
		$timezone = $r[0]['timezone'];
	}

	// Get all events which are owned by a uid (respects permissions).
	$events = events_by_uid($uid);

	// We have the events that are available for the requestor.
	// Now format the output according to the requested format.
	if (count($events)) {
		$res = event_format_export($events, $format, $timezone);
	}

	// If there are results the precess was successfull.
	if (x($res)) {
		$process = true;
	}

	// Get the file extension for the format.
	switch ($format) {
		case "ical":
			$file_ext = "ics";
			break;

		case "csv":
			$file_ext = "csv";
			break;

		default:
			$file_ext = "";
	}

	$arr = [
		'success'   => $process,
		'format'    => $format,
		'extension' => $file_ext,
		'content'   => $res,
	];

	return $arr;
}

/**
 * @brief Get the events widget.
 *
 * @return string Formated html of the evens widget.
 */
function widget_events() {
	$a = get_app();

	$owner_uid = $a->data['user']['uid'];
	// $a->data is only available if the profile page is visited. If the visited page is not part
	// of the profile page it should be the personal /events page. So we can use $a->user.
	$user = ($a->data['user']['nickname'] ? $a->data['user']['nickname'] : $a->user['nickname']);


	// The permission testing is a little bit tricky because we have to respect many cases.

	// It's not the private events page (we don't get the $owner_uid for /events).
	if (! local_user() && ! $owner_uid) {
		return;
	}

	/*
	 * Cal logged in user (test permission at foreign profile page).
	 * If the $owner uid is available we know it is part of one of the profile pages (like /cal).
	 * So we have to test if if it's the own profile page of the logged in user
	 * or a foreign one. For foreign profile pages we need to check if the feature
	 * for exporting the cal is enabled (otherwise the widget would appear for logged in users
	 * on foreigen profile pages even if the widget is disabled).
	 */
	if (intval($owner_uid) && local_user() !== $owner_uid && ! Feature::isEnabled($owner_uid, "export_calendar")) {
		return;
	}

	/*
	 * If it's a kind of profile page (intval($owner_uid)) return if the user not logged in and
	 * export feature isn't enabled.
	 */
	if (intval($owner_uid) && ! local_user() && ! Feature::isEnabled($owner_uid, "export_calendar")) {
		return;
	}

	return replace_macros(get_markup_template("events_aside.tpl"), [
		'$etitle' => L10n::t("Export"),
		'$export_ical' => L10n::t("Export calendar as ical"),
		'$export_csv' => L10n::t("Export calendar as csv"),
		'$user' => $user
	]);
}

/**
 * @brief Format an item array with event data to HTML.
 *
 * @param arr $item Array with item and event data.
 * @return string HTML output.
 */
function format_event_item($item) {
	$same_date = false;
	$finish    = false;

	// Set the different time formats.
	$dformat       = L10n::t('l F d, Y \@ g:i A'); // Friday January 18, 2011 @ 8:01 AM.
	$dformat_short = L10n::t('D g:i A'); // Fri 8:01 AM.
	$tformat       = L10n::t('g:i A'); // 8:01 AM.

	// Convert the time to different formats.
	$dtstart_dt = day_translate(
		$item['event-adjust'] ?
			DateTimeFormat::local($item['event-start'], $dformat)
			: DateTimeFormat::utc($item['event-start'], $dformat)
	);
	$dtstart_title = DateTimeFormat::utc($item['event-start'], $item['event-adjust'] ? DateTimeFormat::ATOM : 'Y-m-d\TH:i:s');
	// Format: Jan till Dec.
	$month_short = day_short_translate(
		$item['event-adjust'] ?
			DateTimeFormat::local($item['event-start'], 'M')
			: DateTimeFormat::utc($item['event-start'], 'M')
	);
	// Format: 1 till 31.
	$date_short = $item['event-adjust'] ?
		DateTimeFormat::local($item['event-start'], 'j')
		: DateTimeFormat::utc($item['event-start'], 'j');
	$start_time = $item['event-adjust'] ?
		DateTimeFormat::local($item['event-start'], $tformat)
		: DateTimeFormat::utc($item['event-start'], $tformat);
	$start_short = day_short_translate(
		$item['event-adjust'] ?
			DateTimeFormat::local($item['event-start'], $dformat_short)
			: DateTimeFormat::utc($item['event-start'], $dformat_short)
	);

	// If the option 'nofinisch' isn't set, we need to format the finish date/time.
	if (!$item['event-nofinish']) {
		$finish = true;
		$dtend_dt  = day_translate(
			$item['event-adjust'] ?
				DateTimeFormat::local($item['event-finish'], $dformat)
				: DateTimeFormat::utc($item['event-finish'], $dformat)
		);
		$dtend_title = DateTimeFormat::utc($item['event-finish'], $item['event-adjust'] ? DateTimeFormat::ATOM : 'Y-m-d\TH:i:s');
		$end_short = day_short_translate(
			$item['event-adjust'] ?
				DateTimeFormat::local($item['event-finish'], $dformat_short)
				: DateTimeFormat::utc($item['event-finish'], $dformat_short)
		);
		$end_time = $item['event-adjust'] ?
			DateTimeFormat::local($item['event-finish'], $tformat)
			: DateTimeFormat::utc($item['event-finish'], $tformat);
		// Check if start and finish time is at the same day.
		if (substr($dtstart_title, 0, 10) === substr($dtend_title, 0, 10)) {
			$same_date = true;
		}
	}

	// Format the event location.
	$evloc = event_location2array($item['event-location']);
	$location = [];

	if (isset($evloc['name'])) {
		$location['name'] = prepare_text($evloc['name']);
	}
	// Construct the map HTML.
	if (isset($evloc['address'])) {
		$location['map'] = '<div class="map">' . Map::byLocation($evloc['address']) . '</div>';
	} elseif (isset($evloc['coordinates'])) {
		$location['map'] = '<div class="map">' . Map::byCoordinates(str_replace('/', ' ', $evloc['coordinates'])) . '</div>';
	}

	// Construct the profile link (magic-auth).
	$sp = false;
	$profile_link = best_link_url($item, $sp);

	if (!$sp) {
		$profile_link = Profile::zrl($profile_link);
	}

	$event = replace_macros(get_markup_template('event_stream_item.tpl'), [
		'$id'             => $item['event-id'],
		'$title'          => prepare_text($item['event-summary']),
		'$dtstart_label'  => L10n::t('Starts:'),
		'$dtstart_title'  => $dtstart_title,
		'$dtstart_dt'     => $dtstart_dt,
		'$finish'         => $finish,
		'$dtend_label'    => L10n::t('Finishes:'),
		'$dtend_title'    => $dtend_title,
		'$dtend_dt'       => $dtend_dt,
		'$month_short'    => $month_short,
		'$date_short'     => $date_short,
		'$same_date'      => $same_date,
		'$start_time'     => $start_time,
		'$start_short'    => $start_short,
		'$end_time'       => $end_time,
		'$end_short'      => $end_short,
		'$author_name'    => $item['author-name'],
		'$author_link'    => $profile_link,
		'$author_avatar'  => $item['author-avatar'],
		'$description'    => prepare_text($item['event-desc']),
		'$location_label' => L10n::t('Location:'),
		'$show_map_label' => L10n::t('Show map'),
		'$hide_map_label' => L10n::t('Hide map'),
		'$map_btn_label'  => L10n::t('Show map'),
		'$location'       => $location
	]);

	return $event;
}

/**
 * @brief Format a string with map bbcode to an array with location data.
 *
 * Note: The string must only contain location data. A string with no bbcode will be
 * handled as location name.
 *
 * @param string $s The string with the bbcode formatted location data.
 *
 * @return array The array with the location data.
 *  'name' => The name of the location,<br>
 * 'address' => The address of the location,<br>
 * 'coordinates' => Latitude‎ and longitude‎ (e.g. '48.864716,2.349014').<br>
 */
function event_location2array($s = '') {
	if ($s == '') {
		return;
	}

	$location = ['name' => $s];

	// Map tag with location name - e.g. [map]Paris[/map].
	if (strpos($s, '[/map]') !== false) {
		$found = preg_match("/\[map\](.*?)\[\/map\]/ism", $s, $match);
		if (intval($found) > 0 && array_key_exists(1, $match)) {
			$location['address'] =  $match[1];
			// Remove the map bbcode from the location name.
			$location['name'] = str_replace($match[0], "", $s);
		}
	// Map tag with coordinates - e.g. [map=48.864716,2.349014].
	} elseif (strpos($s, '[map=') !== false) {
		$found = preg_match("/\[map=(.*?)\]/ism", $s, $match);
		if (intval($found) > 0 && array_key_exists(1, $match)) {
			$location['coordinates'] =  $match[1];
			// Remove the map bbcode from the location name.
			$location['name'] = str_replace($match[0], "", $s);
		}
	}

	return $location;
}
