<?php
/**
 * @file include/event.php
 * @brief functions specific to event handling
 */

function format_event_html($ev, $simple = false) {
	return \Friendica\Model\Event::getHTML($ev, $simple);
}

function format_event_bbcode($ev) {
	return \Friendica\Model\Event::getBBCode($ev);
}

function bbtoevent($s) {
	return \Friendica\Model\Event::fromBBCode($s);
}

function sort_by_date($a) {
	return \Friendica\Model\Event::sortByDate($a);
}

function event_delete($event_id) {
	 \Friendica\Model\Event::delete($event_id);
}

function event_store($arr) {
	return \Friendica\Model\Event::store($arr);
}

function get_event_strings() {
	return \Friendica\Model\Event::getStrings();
}

function event_remove_duplicates($dates) {
	return \Friendica\Model\Event::removeDuplicates($dates);
}

function event_by_id($owner_uid = 0, $event_params, $sql_extra = '') {
	return \Friendica\Model\Event::getListById($owner_uid, $event_params['event-id'], $sql_extra);
}

function events_by_date($owner_uid = 0, $event_params, $sql_extra = '') {
	$event_params['ignore'] = $event_params['ignored'];
	return \Friendica\Model\Event::getListByDate($owner_uid, $event_params, $sql_extra);
}

function process_events($arr) {
	return \Friendica\Model\Event::prepareListForTemplate($arr);
}

function event_format_export($events, $format = 'ical', $timezone)
{
	return \Friendica\Model\Event::formatListForExport($events, $format, $timezone);
}

function events_by_uid($uid = 0, $sql_extra = '') {
	return \Friendica\Model\Event::getListByUserId($uid);
}

function event_export($uid, $format = 'ical') {
	return \Friendica\Model\Event::exportListByUserId($uid, $format);
}

function widget_events() {
	return \Friendica\Content\Widget\CalendarExport::getHTML();
}

function format_event_item($item) {
	return \Friendica\Model\Event::getItemHTML($item);
}

function event_location2array($s = '') {
	return \Friendica\Model\Event::locationToArray($s);
}
